import {Alpine, Livewire} from '../../vendor/livewire/livewire/dist/livewire.esm';

function clipboard(subject) {
    return new Promise(function (resolve, reject) {
        let success = false;

        function listener(e) {
            e.clipboardData.setData("text/plain", subject);
            e.preventDefault();
            success = true;
        }

        document.addEventListener("copy", listener);
        document.execCommand("copy");
        document.removeEventListener("copy", listener);
        success ? resolve() : reject();
    });
};

Alpine.magic('clipboard', () => async subject => {
    await clipboard(subject)
    Livewire.dispatch('clipboard:copied', {text: subject})
})

Livewire.start()
