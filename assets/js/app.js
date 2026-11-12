document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.getElementById(button.getAttribute('data-toggle-password'));
            if (!input) return;
            var visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            button.textContent = visible ? 'Ẩn' : 'Hiện';
        });
    });

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.getAttribute('data-confirm'))) event.preventDefault();
        });
    });

    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (event.defaultPrevented) return;
            var button = event.submitter || form.querySelector('button[type="submit"]');
            if (!button || (!button.formNoValidate && !form.checkValidity())) return;
            button.dataset.originalText = button.textContent;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Đang xử lý';
        });
    });

    document.querySelectorAll('[data-admin-search]').forEach(function (input) {
        input.addEventListener('input', function () {
            var query = input.value.toLocaleLowerCase();
            document.querySelectorAll('.admin-panel tbody tr').forEach(function (row) {
                row.hidden = row.textContent.toLocaleLowerCase().indexOf(query) === -1;
            });
        });
    });
});
