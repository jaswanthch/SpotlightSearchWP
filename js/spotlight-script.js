// Vanilla JavaScript for Spotlight Search Plugin with Confirmation and Toast Notifications

document.addEventListener('DOMContentLoaded', function () {
    // Check if the current user is an admin
    if (!essp_ajax_object.is_admin_user) {
        return;
    }

    // Create the modal HTML
    var esspModal = document.createElement('div');
    esspModal.id = 'essp-modal';
    esspModal.innerHTML = '\
    <div id="essp-modal-content">\
        <input type="text" id="essp-search-input" placeholder="Search..." autocomplete="off" />\
        <ul id="essp-results"></ul>\
    </div>';

    document.body.appendChild(esspModal);

    // Create toast notification container
    var esspToast = document.createElement('div');
    esspToast.id = 'essp-toast';
    document.body.appendChild(esspToast);

    // Elements
    var esspInput = document.getElementById('essp-search-input');
    var esspResults = document.getElementById('essp-results');

    // Other variables
    var typingTimer;
    var doneTypingInterval = 300;
    var selectedIndex = -1;


    // show modal on shift + /
    document.addEventListener('keydown', function (e) {
        if (e.shiftKey && e.keyCode === 191) {
            e.preventDefault();
            openSearchModal();
        }
    });

    function openSearchModal() {
        esspModal.style.display = 'block';
        esspInput.value = '';
        esspResults.innerHTML = '';
        esspInput.focus();
    }

    // to show a prompt when user 'removes' individual admin options

    document.addEventListener('DOMContentLoaded', function () {
        const tableBody = document.querySelector('#essp-admin-pages-table tbody');

        tableBody.addEventListener('click', function (e) {
            if (e.target && e.target.classList.contains('essp-remove-admin-page')) {
                const confirmed = confirm('Are you sure you want to remove this Quick Admin Navigation shortcut?');
                if (!confirmed) {
                    e.preventDefault(); // Prevent the default action if not confirmed
                } else {
                    const row = e.target.closest('tr');
                    if (row) {
                        row.remove();
                    }
                }
            }
        });
    });

    // Hide modal on Escape
    document.addEventListener('keydown', function (e) {
        if (e.keyCode === 27) { // Escape key
            esspModal.style.display = 'none';
        }
    });

    // Hide modal when clicking outside
    document.addEventListener('mousedown', function (e) {
        if (!esspModal.contains(e.target)) {
            esspModal.style.display = 'none';
        }
    });

    // Handle input and keyboard navigation
    esspInput.addEventListener('keydown', function (e) {
        var items = esspResults.querySelectorAll('li');

        if (e.keyCode === 40) { // Down arrow
            selectedIndex = (selectedIndex + 1) % items.length;
            updateSelection(items);
            e.preventDefault();
        } else if (e.keyCode === 38) { // Up arrow
            selectedIndex = (selectedIndex - 1 + items.length) % items.length;
            updateSelection(items);
            e.preventDefault();
        } else if (e.keyCode === 13) { // Enter key
            e.preventDefault();
            if (selectedIndex >= 0 && items[selectedIndex]) {
                var link = items[selectedIndex].dataset.link;
                window.location.href = link;
            } else {
                var query = esspInput.value.trim();
                // Check if it's a 'new' command
                if (/^new\s+/i.test(query) && essp_ajax_object.current_user_can_create) {
                    // Show confirmation prompt
                    showConfirmationPrompt(query);
                } else {
                    esspPerformSearch(query, true);
                }
            }
        } else {
            selectedIndex = -1;
        }
    });

    esspInput.addEventListener('keyup', function (e) {
        if (![13, 38, 40].includes(e.keyCode)) {
            clearTimeout(typingTimer);
            var query = esspInput.value.trim();

            if (query.length > 0) {
                typingTimer = setTimeout(function () {
                    // Avoid sending 'new' commands during search
                    if (!/^new\s+/i.test(query)) {
                        esspPerformSearch(query);
                    } else {
                        esspResults.innerHTML = '';
                    }
                }, doneTypingInterval);
            } else {
                esspResults.innerHTML = '';
            }
        }
    });

    function updateSelection(items) {
        items.forEach(function (item, index) {
            if (index === selectedIndex) {
                item.classList.add('selected');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('selected');
            }
        });
    }

    function esspPerformSearch(query, immediate) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', essp_ajax_object.ajax_url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

        xhr.onload = function () {
            if (xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);

                if (response.status === 'success') {
                    esspResults.innerHTML = '';

                    if (response.results && response.results.length > 0) {
                        response.results.forEach(function (item) {
                            var li = document.createElement('li');
                            li.textContent = item.title;

                            if (item.type === 'admin_page') {
                                li.classList.add('admin-page'); // Optional, for styling
                            }

                            li.dataset.link = item.link;
                            esspResults.appendChild(li);
                        });
                    } else {
                        var li = document.createElement('li');
                        li.textContent = 'No results found.';
                        esspResults.appendChild(li);
                    }

                    // If immediate action and only one result, redirect
                    if (immediate && response.results && response.results.length === 1) {
                        window.location.href = response.results[0].link;
                    }
                } else {
                    var li = document.createElement('li');
                    li.textContent = 'Error: ' + response.message;
                    esspResults.appendChild(li);
                }
            } else {
                var li = document.createElement('li');
                li.textContent = 'Error performing search.';
                esspResults.appendChild(li);
            }
        };

        var data = 'action=essp_search&security=' + encodeURIComponent(essp_ajax_object.essp_nonce) + '&query=' + encodeURIComponent(query) + '&is_create=false';

        // Include admin pages if the feature is enabled
        if (essp_ajax_object.essp_settings.enable_admin_pages == '1') {
            data += '&include_admin_pages=true';
        }

        xhr.send(data);
    }

    // Handle click on results
    esspResults.addEventListener('click', function (e) {
        var target = e.target;
        if (target && target.tagName === 'LI') {
            var link = target.dataset.link;
            if (link) {
                window.location.href = link;
            }
        }
    });

    // Confirmation Prompt
    function showConfirmationPrompt(query) {
        var promptModal = document.createElement('div');
        promptModal.id = 'essp-prompt-modal';
        promptModal.innerHTML = '\
        <div id="essp-prompt-content">\
            <p>Do you want to create a new item titled "<strong>' + escapeHtml(query.substring(4).trim()) + '</strong>"?</p>\
            <button id="essp-create-view">Create &amp; View</button>\
            <button id="essp-create-add-more">Create &amp; Add More</button>\
            <button id="essp-confirm-cancel">Cancel</button>\
        </div>';
        document.body.appendChild(promptModal);

        // Handle 'Create & View'
        document.getElementById('essp-create-view').addEventListener('click', function () {
            document.body.removeChild(promptModal);
            createNewItem(query, true); // Pass true to indicate redirection
        });

        // Handle 'Create & Add More'
        document.getElementById('essp-create-add-more').addEventListener('click', function () {
            document.body.removeChild(promptModal);
            createNewItem(query, false); // Pass false to continue adding more
        });

        // Handle 'Cancel'
        document.getElementById('essp-confirm-cancel').addEventListener('click', function () {
            document.body.removeChild(promptModal);
            esspInput.focus();
        });
    }

    // Function to create new item
    function createNewItem(query, redirect) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', essp_ajax_object.ajax_url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

        xhr.onload = function () {
            if (xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);

                if (response.status === 'success') {
                    if (redirect && response.view_link) {
                        window.location.href = response.view_link;
                    } else {
                        // Show toast notification
                        showToast(response.message);
                        // Clear input and results
                        esspInput.value = '';
                        esspResults.innerHTML = '';
                        // Reopen the search modal and focus on input
                        esspModal.style.display = 'block';
                        esspInput.focus();
                    }
                } else {
                    showToast('Error: ' + response.message);
                }
            } else {
                showToast('Error creating new item.');
            }
        };

        var data = 'action=essp_search&security=' + encodeURIComponent(essp_ajax_object.essp_nonce) + '&query=' + encodeURIComponent(query) + '&is_create=true';
        xhr.send(data);
    }

    // Function to show toast notification
    function showToast(message) {
        esspToast.textContent = message;
        esspToast.className = 'show';
        setTimeout(function () {
            esspToast.className = esspToast.className.replace('show', '');
        }, 3000);
    }

    // Utility function to escape HTML
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function (m) { return map[m]; });
    }
});
