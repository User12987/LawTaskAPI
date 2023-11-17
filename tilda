```js

// Script to handle form actions and send lead to CRM using instructions: https://telegra.ph/API-06-17, https://help.tilda.cc/tips/javascript

async function sendToCRM(form) {
    if (!form) return;

    var formData = new FormData(form);
    try {
        const response = await fetch('https://lawtask.pro/API/api.php', {
            method: 'POST',
            body: formData,
        });

        if (!response.ok) {
            throw new Error('Network error: ' + response.statusText);
        }

        const data = await response.json();
        if (data?.status !== 'success') {
            throw new Error('Error: ' + data.reason);
        }

    } catch (error) {
        console.error(error.message);
    }
}

// Init form listener
function initFormListener() {
    const forms = document.querySelectorAll('.js-form-proccess');
    Array.prototype.forEach.call(forms, function (form) {
        form.addEventListener('tildaform:aftersuccess', function () {
            sendToCRM(form);
        });
    });
}

if (document.readyState !== 'loading') {
    initFormListener();
} else {
    document.addEventListener('DOMContentLoaded', initFormListener);
}

```
