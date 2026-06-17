<?php

namespace App\Livewire\Admin;

use App\Actions\Admin\CreateUser;
use App\Actions\Admin\UpdateUser;
use App\Actions\DeleteUserAccount;
use App\Models\Properties\ResourceType;
use App\Models\Properties\UserStatus;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class UserManagement extends Component
{
    use Toast, WithPagination;

    /** @var list<string> Columns that can safely be sorted on. */
    private const SORTABLE = ['name', 'email', 'created_at'];

    public string $search = '';

    /** @var array{column: string, direction: string} */
    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];

    /* CREATE / EDIT */
    public bool $showUserModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public ?string $password = null;

    public bool $isAdmin = false;

    public int $status = UserStatus::ENABLED->value;

    public bool $unlimitedQuota = true;

    public ?int $quotaMb = null;

    /* DELETE */
    public bool $confirmingDelete = false;

    public ?int $deletingId = null;

    public function mount(): void
    {
        abort_unless(Gate::allows('administrate'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * The paginated, searchable and sortable list of users.
     */
    #[Computed]
    public function users(): LengthAwarePaginator
    {
        $column = in_array($this->sortBy['column'] ?? null, self::SORTABLE, true) ? $this->sortBy['column'] : 'name';
        $direction = ($this->sortBy['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return User::query()
            ->withCount(['resources as media_count' => fn ($query) => $query->where('type', '!=', ResourceType::DIRECTORY->value)])
            ->when($this->search !== '', function ($query) {
                $term = '%'.$this->search.'%';
                $query->where(fn ($inner) => $inner->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->orderBy($column, $direction)
            ->paginate(10);
    }

    /**
     * Options for the status select, derived from {@see UserStatus}.
     *
     * @return list<array{id: int, name: string}>
     */
    #[Computed]
    public function statusOptions(): array
    {
        return collect(UserStatus::cases())
            ->map(fn (UserStatus $status) => ['id' => $status->value, 'name' => $status->label()])
            ->all();
    }

    public function openCreate(): void
    {
        $this->reset(['name', 'email', 'password', 'isAdmin', 'quotaMb']);
        $this->status = UserStatus::ENABLED->value;
        $this->unlimitedQuota = true;
        $this->editingId = null;
        $this->resetValidation();
        $this->showUserModal = true;
    }

    public function openEdit(int $id): void
    {
        $user = User::query()->findOrFail($id);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = null;
        $this->isAdmin = $user->is_admin;
        $this->status = $user->status->value;
        $this->unlimitedQuota = $user->quota < 0;
        $this->quotaMb = $user->quota < 0 ? null : (int) round($user->quota / (1024 * 1024));

        $this->resetValidation();
        $this->showUserModal = true;
    }

    public function saveUser(CreateUser $createUser, UpdateUser $updateUser): void
    {
        $this->validate($this->rules(), attributes: ['quotaMb' => __('quota')]);

        $attributes = [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'is_admin' => $this->isAdmin,
            'status' => UserStatus::from($this->status),
            'quota' => $this->unlimitedQuota ? -1 : (int) round((int) $this->quotaMb * 1024 * 1024),
        ];

        if ($this->editingId === null) {
            $user = $createUser($attributes);
            $event = 'user.created';
            $message = __('User created successfully!');
        } else {
            $user = User::query()->findOrFail($this->editingId);

            // Demoting the only administrator would lock everyone out of the admin area.
            if ($user->is_admin && ! $this->isAdmin && $this->isLastAdmin($user)) {
                $this->error(__('You cannot remove the last administrator.'));

                return;
            }

            $user = $updateUser($user, $attributes);
            $event = 'user.updated';
            $message = __('User updated successfully!');
        }

        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log($event);

        $this->showUserModal = false;
        unset($this->users);

        $this->success($message);
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->confirmingDelete = true;
    }

    public function deleteUser(DeleteUserAccount $deleteUserAccount): void
    {
        $user = User::query()->find($this->deletingId);

        if (! $user) {
            $this->error(__('User not found.'));

            return;
        }

        if ($user->id === auth()->id()) {
            $this->error(__('You cannot delete your own account here.'));

            return;
        }

        if ($this->isLastAdmin($user)) {
            $this->error(__('You cannot delete the last administrator.'));

            return;
        }

        $deleteUserAccount($user);

        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log('user.deleted');

        $this->confirmingDelete = false;
        $this->deletingId = null;
        unset($this->users);

        $this->success(__('User deleted successfully!'));
    }

    public function render(): object
    {
        return view('livewire.admin.user-management');
    }

    /**
     * Validation rules for the create/edit form.
     *
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($this->editingId)],
            'password' => $this->editingId === null
                ? ['required', 'string', Password::default()]
                : ['nullable', 'string', Password::default()],
            'status' => [Rule::enum(UserStatus::class)],
            'quotaMb' => $this->unlimitedQuota ? ['nullable'] : ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Whether the given user is the last remaining administrator.
     */
    private function isLastAdmin(User $user): bool
    {
        return $user->is_admin && User::query()->where('is_admin', true)->count() <= 1;
    }
}
