# App Structure
- The app is composed of 2 main components: `core` and `app`; we are in the core component.
- The `core` component is basically a fully fledged Laravel app with Livewire that implements all the logic of the application.
- The `app` component is a "installation skeleton", a minimal set of config and boostrap, that installs the core component as a Composer package, doing some remapping. This allows to upgrade and downgrade with ease.
- Configs, such a global settings, and per user settings, are managed via Laravel Pennant.

# Architecture
- The app business logic is organized in Actions, in the app/Actions directories. Each action should act as a small building block, should be agnostic about web context or console or jobs.
- The app expose a Livewire app, as well a versioned REST API.

# Code style
- All the code, docs and comments must be in English.
- Use comments to explain code only when is necessary, do not comment obvious code.

# Tools
- Phpstan, Pint and Pest test suites must pass.

# Documentation
- The main website also act as documentation is in the docs/ folder, its a static site generated with Vitepress.
- Keep the documentation up to date, for relevant changes to features described in the docs.

# Development Tips
- The Vite development server is usually already running, so its not necessary to run build commands.
- The master branch is the next written from scratch app with the above structure, the 3.x branch contains the legacy app.
- The master branch is acting as a monorepo, in the root has the app/ and core/ folders.
