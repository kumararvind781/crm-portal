</div>
<script src="<?= BASE_URL ?>assets/js/app.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/jquery/dist/jquery.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        const systemSelect = document.getElementById('system_select');
        const systemsInput = document.getElementById('systems_used');
        const selectedBox = document.getElementById('selectedSystems');
        const otherDiv = document.getElementById('otherDiv');
        const otherInput = document.getElementById('otherSystem');

        if (!systemSelect || !systemsInput || !selectedBox) {
            return;
        }

        let selectedSystems = systemsInput.value
            .split(',')
            .map(function (item) {
                return item.trim();
            })
            .filter(function (item) {
                return item !== '';
            });

        selectedSystems = [...new Set(selectedSystems)];

        function updateHiddenInput() {
            systemsInput.value = selectedSystems.join(',');
        }

        function renderSelectedSystems() {
            selectedBox.innerHTML = '';

            selectedSystems.forEach(function (systemName) {
                const tag = document.createElement('span');

                tag.style.cssText = `
                display: inline-flex;
                align-items: center;
                gap: 7px;
                margin: 4px;
                padding: 7px 12px;
                border-radius: 18px;
                background: #1677ff;
                color: #ffffff;
                font-size: 14px;
            `;

                const text = document.createElement('span');
                text.textContent = systemName;

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.textContent = '×';

                removeButton.style.cssText = `
                border: 0;
                padding: 0;
                background: transparent;
                color: #ffffff;
                cursor: pointer;
                font-size: 18px;
                line-height: 1;
            `;

                removeButton.addEventListener('click', function () {
                    selectedSystems = selectedSystems.filter(function (item) {
                        return item !== systemName;
                    });

                    updateHiddenInput();
                    renderSelectedSystems();
                });

                tag.appendChild(text);
                tag.appendChild(removeButton);
                selectedBox.appendChild(tag);
            });
        }

        systemSelect.addEventListener('change', function () {
            const selectedValue = systemSelect.value;

            if (selectedValue === '') {
                return;
            }

            if (selectedValue === '__other__') {
                otherDiv.style.display = 'block';
                otherInput.focus();
                systemSelect.value = '';
                return;
            }

            if (!selectedSystems.includes(selectedValue)) {
                selectedSystems.push(selectedValue);
            }

            updateHiddenInput();
            renderSelectedSystems();

            // Reset dropdown after adding.
            systemSelect.selectedIndex = 0;
        });

        function addOtherSystem() {
            const otherValue = otherInput.value.trim();

            if (otherValue !== '' && !selectedSystems.includes(otherValue)) {
                selectedSystems.push(otherValue);
            }

            otherInput.value = '';
            otherDiv.style.display = 'none';

            updateHiddenInput();
            renderSelectedSystems();
            systemSelect.selectedIndex = 0;
        }

        otherInput.addEventListener('change', addOtherSystem);

        otherInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                addOtherSystem();
            }
        });

        updateHiddenInput();
        renderSelectedSystems();
    });
</script>

<style>
    /* Disable text selection across CRM */
    body {
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
    }

    /* Keep form fields editable and selectable */
    input,
    textarea,
    select,
    [contenteditable="true"],
    .allow-copy {
        -webkit-user-select: text;
        -moz-user-select: text;
        -ms-user-select: text;
        user-select: text;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        function isEditable(element) {
            return element.closest(
                'input, textarea, select, [contenteditable="true"], .allow-copy'
            );
        }

        // Block Ctrl+C, Ctrl+X and Ctrl+V outside form fields
        document.addEventListener('keydown', function (event) {
            if ((event.ctrlKey || event.metaKey) &&
                ['c', 'x', 'v'].includes(event.key.toLowerCase()) &&
                !isEditable(event.target)) {
                event.preventDefault();
            }
        });

        // Block right-click outside form fields
        document.addEventListener('contextmenu', function (event) {
            if (!isEditable(event.target)) {
                event.preventDefault();
            }
        });

        // Block copy and cut outside form fields
        document.addEventListener('copy', function (event) {
            if (!isEditable(event.target)) {
                event.preventDefault();
            }
        });

        document.addEventListener('cut', function (event) {
            if (!isEditable(event.target)) {
                event.preventDefault();
            }
        });

        // Block drag-to-copy
        document.addEventListener('dragstart', function (event) {
            if (!isEditable(event.target)) {
                event.preventDefault();
            }
        });

    });
</script>
</body>

</html>