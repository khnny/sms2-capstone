(function () {
    'use strict';
    var form = document.getElementById('panelApplicationForm');
    if (!form) return;

    var steps = Array.from(form.querySelectorAll('[data-step]'));
    var indicators = Array.from(form.querySelectorAll('[data-step-indicator]'));
    var current = 0;
    var previous = document.getElementById('previousStep');
    var next = document.getElementById('nextStep');
    var prepare = document.getElementById('prepareSubmit');
    var dialog = document.getElementById('submitConfirm');
    var progress = document.getElementById('progressFill');

    function render() {
        steps.forEach(function (step, index) {
            step.hidden = index !== current;
        });

        indicators.forEach(function (item, index) {
            item.classList.toggle('is-active', index === current);
            item.classList.toggle('is-done', index < current);
        });

        document.getElementById('stepLabel').textContent = 'Step ' + (current + 1) + ' of ' + steps.length;
        document.getElementById('stepName').textContent = indicators[current].querySelector('b').textContent;
        progress.style.width = ((current + 1) / steps.length * 100) + '%';

        previous.hidden = current === 0;
        next.hidden = current === steps.length - 1;
        prepare.hidden = current !== steps.length - 1;

        if (current === steps.length - 1) {
            renderReview();
        }

        var firstControl = steps[current].querySelector('input, textarea, select');
        if (firstControl) firstControl.focus({ preventScroll: true });
    }

    function validStep() {
        var controls = Array.from(steps[current].querySelectorAll('input[required], textarea[required]'));
        for (var i = 0; i < controls.length; i += 1) {
            if (!controls[i].checkValidity()) {
                controls[i].reportValidity();
                return false;
            }
        }
        return true;
    }

    function readFieldValue(name) {
        var field = form.elements.namedItem(name);
        if (!field) return '';
        if (field instanceof RadioNodeList) {
            var checked = Array.from(field).find(function (item) {
                return item.checked;
            });
            return checked ? checked.value.trim() : '';
        }
        if (Array.isArray(field)) {
            return field.map(function (item) {
                return (item.value || '').trim();
            }).filter(Boolean).join(', ');
        }
        return (field.value || '').trim();
    }

    function readCheckboxValue(name) {
        var checked = form.querySelectorAll('input[name="' + name + '[]"]:checked');
        return Array.from(checked).map(function (input) {
            return input.value.trim();
        }).filter(Boolean).join(', ');
    }

    function renderReview() {
        var review = document.getElementById('applicationReview');
        var labels = [
            'Full name',
            'Email address',
            'Contact number',
            'Institution',
            'College / department',
            'Position',
            'Primary specialization',
            'Secondary specialization',
            'Research areas',
            'Keywords',
            'Years of research experience',
            'Qualified defense roles',
            'Available days',
            'Preferred schedule'
        ];
        var fields = [
            'applicant_name',
            'applicant_email',
            'applicant_phone',
            'institution',
            'college_department',
            'position',
            'primary_specialization',
            'secondary_specialization',
            'research_areas',
            'expertise_keywords',
            'years_research_experience',
            null,
            'available_days',
            'preferred_schedule'
        ];

        review.innerHTML = labels.map(function (label, index) {
            var value = fields[index] ? readFieldValue(fields[index]) : readCheckboxValue('defense_preferences');
            if (!value) value = 'Not provided';
            return '<div><small>' + label + '</small><strong>' + escapeHtml(value) + '</strong></div>';
        }).join('');
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (char) {
            var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return map[char] || char;
        });
    }

    next.addEventListener('click', function () {
        if (validStep() && current < steps.length - 1) {
            current += 1;
            render();
        }
    });

    previous.addEventListener('click', function () {
        if (current > 0) {
            current -= 1;
            render();
        }
    });

    prepare.addEventListener('click', function () {
        if (validStep() && dialog && typeof dialog.showModal === 'function') {
            dialog.showModal();
        }
    });

    document.getElementById('confirmSubmit').addEventListener('click', function (event) {
        event.preventDefault();
        var submitter = document.createElement('input');
        submitter.type = 'hidden';
        submitter.name = 'application_action';
        submitter.value = 'submit';
        form.appendChild(submitter);
        form.requestSubmit();
    });

    form.addEventListener('submit', function (event) {
        if (!form.querySelector('[name="application_action"][value="submit"]')) {
            var submitter = event.submitter;
            if (!submitter || submitter.name !== 'application_action' || submitter.value !== 'draft') {
                event.preventDefault();
            }
        }
    });

    render();
}());
