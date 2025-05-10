<?php
ob_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Management</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        :root {
            --prmsu-gray-dark: #333333;
            --prmsu-gray: #666666;
            --prmsu-gray-light: #f5f5f5;
            --prmsu-gold: #EFBB0F;
            --prmsu-gold-light: #F9F3E5;
            --prmsu-white: #ffffff;
            --solid-green: #D1E7DD;
        }

        .bg-prmsu-gold {
            background-color: var(--prmsu-gold);
        }

        .hover\:bg-prmsu-gold-dark:hover {
            background-color: #d4a00d;
        }

        .bg-prmsu-gold-light {
            background-color: var(--prmsu-gold-light);
        }

        .bg-prmsu-gray-light {
            background-color: var(--prmsu-gray-light);
        }

        .text-prmsu-gray-dark {
            color: var(--prmsu-gray-dark);
        }

        .border-prmsu-gold {
            border-color: var(--prmsu-gold);
        }

        .bg-solid-green {
            background-color: var(--solid-green);
        }

        /* Search bar specific styles */
        .search-container {
            position: relative;
        }

        .search-input {
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: var(--prmsu-gold);
            box-shadow: 0 0 0 3px rgba(239, 187, 15, 0.2);
        }

        .suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-height: 200px;
            overflow-y: auto;
            z-index: 10;
        }

        .suggestion-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .suggestion-item:hover {
            background-color: var(--prmsu-gold-light);
        }

        .loading::after {
            content: '';
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #EFBB0F;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-left: 8px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            let searchTimeout;

            // Debounced search
            $('#search-input').on('input', function() {
                const query = $(this).val().trim();
                const feedback = $('#search-feedback');
                const suggestions = $('#suggestions');

                clearTimeout(searchTimeout);

                if (query.length < 2) {
                    feedback.text('');
                    suggestions.hide().empty();
                    renderSearchResults([]);
                    return;
                }

                feedback.text('Searching...').addClass('loading text-gray-600').removeClass('text-green-500 text-red-500');

                searchTimeout = setTimeout(() => {
                    $.ajax({
                        url: '/chair/faculty/search',
                        method: 'POST',
                        data: {
                            name: query
                        },
                        success: function(data) {
                            if (data.length > 0) {
                                feedback.text('Faculty found').removeClass('loading text-gray-600 text-red-500').addClass('text-green-500');
                                renderSuggestions(data);
                                renderSearchResults(data);
                            } else {
                                feedback.text('No faculty found').removeClass('loading text-gray-600 text-green-500').addClass('text-red-500');
                                suggestions.hide().empty();
                                renderSearchResults([]);
                            }
                        },
                        error: function() {
                            feedback.text('Error searching faculty').removeClass('loading text-gray-600 text-green-500').addClass('text-red-500');
                            suggestions.hide().empty();
                            renderSearchResults([]);
                        }
                    });
                }, 300);
            });

            // Hide suggestions when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.search-container').length) {
                    $('#suggestions').hide();
                }
            });

            // Handle suggestion click
            $(document).on('click', '.suggestion-item', function() {
                const name = $(this).text();
                $('#search-input').val(name);
                $('#suggestions').hide();
                $('#search-feedback').text('Faculty found').removeClass('text-red-500').addClass('text-green-500');
                // Trigger search with selected name
                $.ajax({
                    url: '/chair/faculty/search',
                    method: 'POST',
                    data: {
                        name: name
                    },
                    success: function(data) {
                        renderSearchResults(data);
                    }
                });
            });

            // Render autocomplete suggestions
            function renderSuggestions(results) {
                const suggestions = $('#suggestions');
                suggestions.empty();

                if (results.length === 0) {
                    suggestions.hide();
                    return;
                }

                results.forEach(result => {
                    suggestions.append(`
                        <div class="suggestion-item">${result.first_name} ${result.last_name}</div>
                    `);
                });

                suggestions.show();
            }

            // Render search results dynamically
            function renderSearchResults(results) {
                const container = $('#search-results');
                container.empty();

                if (results.length === 0) {
                    container.html('<p class="text-gray-500 text-center py-4">No faculty members found matching your criteria.</p>');
                    return;
                }

                let html = `
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-gray-800 border-b-2 border-prmsu-gold pb-2 mb-6 font-semibold text-xl">Search Results</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full rounded-lg overflow-hidden">
                                <thead>
                                    <tr class="bg-prmsu-gold-light text-gray-800 uppercase text-sm">
                                        <th class="py-4 px-4 text-left">Employee ID</th>
                                        <th class="py-4 px-4 text-left">Name</th>
                                        <th class="py-4 px-4 text-left">College</th>
                                        <th class="py-4 px-4 text-left">Department</th>
                                        <th class="py-4 px-4 text-left">Academic Rank</th>
                                        <th class="py-4 px-4 text-left">Employment Type</th>
                                        <th class="py-4 px-4 text-left">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;

                results.forEach(result => {
                    html += `
                        <tr class="border-b border-gray-100 hover:bg-[rgba(239,187,15,0.1)] transition-colors">
                            <td class="py-4 px-4">${result.employee_id}</td>
                            <td class="py-4 px-4">${result.first_name} ${result.last_name}</td>
                            <td class="py-4 px-4">${result.college_name}</td>
                            <td class="py-4 px-4">${result.department_name}</td>
                            <td class="py-4 px-4">${result.academic_rank}</td>
                            <td class="py-4 px-4">${result.employment_type}</td>
                            <td class="py-4 px-4">
                                <button class="bg-green-500 text-white py-2 px-3 rounded text-sm font-medium hover:bg-green-600 transition-all include-btn" 
                                        data-id="${result.user_id}" data-name="${result.first_name} ${result.last_name}">
                                    Include
                                </button>
                            </td>
                        </tr>
                    `;
                });

                html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;

                container.html(html);
            }

            // Include Faculty Modal
            $(document).on('click', '.include-btn', function() {
                const userId = $(this).data('id');
                const facultyName = $(this).data('name');

                $('#modal-user-id').val(userId);
                $('#modal-faculty-name').text(facultyName);
                $('#include-modal').fadeIn();
            });

            // Remove Faculty Modal
            $(document).on('click', '.remove-btn', function() {
                const userId = $(this).data('id');
                const facultyName = $(this).data('name');

                $('#remove-modal-user-id').val(userId);
                $('#remove-modal-faculty-name').text(facultyName);
                $('#remove-modal').fadeIn();
            });

            // Close Modals
            $('.close, #include-modal, #remove-modal').on('click', function(e) {
                if (e.target.className.includes('close') || e.target.id === 'include-modal' || e.target.id === 'remove-modal') {
                    $('#include-modal, #remove-modal').fadeOut();
                }
            });

            // Confirm Include Faculty
            $('#confirm-include').on('click', function() {
                const userId = $('#modal-user-id').val();
                const formData = new FormData();
                formData.append('user_id', userId);
                formData.append('add_faculty', '1');

                $.ajax({
                    url: window.location.href,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function() {
                        location.reload();
                    },
                    error: function() {
                        alert('Failed to include faculty. Please try again.');
                    }
                });
            });

            // Confirm Remove Faculty
            $('#confirm-remove').on('click', function() {
                const userId = $('#remove-modal-user-id').val();
                const formData = new FormData();
                formData.append('user_id', userId);
                formData.append('remove_faculty', '1');

                $.ajax({
                    url: window.location.href,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function() {
                        location.reload();
                    },
                    error: function() {
                        alert('Failed to remove faculty. Please try again.');
                    }
                });
            });

            $('#cancel-include, #cancel-remove').on('click', function() {
                $('#include-modal, #remove-modal').fadeOut();
            });
        });
    </script>
</head>

<body class="bg-prmsu-gray-light">
    <div class="container mx-auto p-6">
        <h2 class="text-2xl font-semibold text-prmsu-gray-dark border-b-2 border-prmsu-gold pb-2 mb-6">Faculty Management</h2>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 text-red-800 p-4 rounded-lg mb-6 text-center"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="bg-solid-green text-prmsu-gray-dark p-4 rounded-lg mb-6 text-center"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Search Bar -->
        <div class="search-container bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center bg-gray-100 rounded-full p-3 shadow-inner">
                <svg class="w-5 h-5 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" id="search-input" class="search-input flex-1 bg-transparent outline-none text-gray-700 placeholder-gray-500"
                    placeholder="Search faculty by name..." autocomplete="off">
                <span id="search-feedback" class="ml-3 text-sm font-medium"></span>
            </div>
            <div id="suggestions" class="suggestions hidden"></div>
        </div>

        <!-- Search Results -->
        <div id="search-results"></div>

        <!-- Faculty Table -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 transition-transform hover:translate-y-[-5px] hover:shadow-lg">
            <h3 class="text-gray-800 border-b-2 border-prmsu-gold pb-2 mb-6 font-semibold text-xl">Your Department's Faculty</h3>
            <div class="overflow-x-auto">
                <table class="w-full rounded-lg overflow-hidden">
                    <thead>
                        <tr class="bg-prmsu-gold-light text-gray-800 uppercase text-sm">
                            <th class="py-4 px-4 text-left">Employee ID</th>
                            <th class="py-4 px-4 text-left">Name</th>
                            <th class="py-4 px-4 text-left">Academic Rank</th>
                            <th class="py-4 px-4 text-left">Employment Type</th>
                            <th class="py-4 px-4 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($faculty)): ?>
                            <tr>
                                <td colspan="5" class="py-8 px-4 text-center text-gray-500 bg-gray-50">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        <p class="text-gray-600 font-medium">No faculty members found in your department</p>
                                        <p class="text-gray-500 text-sm mt-1">Search for faculty to include them</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($faculty as $member): ?>
                                <tr class="border-b border-gray-100 hover:bg-[rgba(239,187,15,0.1)] transition-colors">
                                    <td class="py-4 px-4"><?php echo htmlspecialchars($member['employee_id']); ?></td>
                                    <td class="py-4 px-4"><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></td>
                                    <td class="py-4 px-4"><?php echo htmlspecialchars($member['academic_rank']); ?></td>
                                    <td class="py-4 px-4"><?php echo htmlspecialchars($member['employment_type']); ?></td>
                                    <td class="py-4 px-4">
                                        <button class="bg-red-600 text-white py-2 px-3 rounded text-sm font-medium hover:bg-red-700 transition-all remove-btn"
                                            data-id="<?php echo $member['user_id']; ?>"
                                            data-name="<?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>">
                                            Remove
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Include Faculty Modal -->
        <div id="include-modal" class="fixed inset-0 bg-black bg-opacity-50 items-center justify-center hidden z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-md relative shadow-xl">
                <span class="close absolute top-4 right-4 text-gray-500 cursor-pointer hover:text-gray-700 text-2xl">×</span>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Include Faculty</h3>
                <p class="text-gray-600 mb-6">Are you sure you want to include <strong id="modal-faculty-name"></strong> in your department?</p>
                <input type="hidden" id="modal-user-id" name="user_id">
                <div class="flex justify-end space-x-3">
                    <button id="confirm-include" class="bg-green-500 text-white py-2 px-4 rounded font-medium hover:bg-green-600 transition-all">Confirm</button>
                    <button id="cancel-include" class="bg-red-500 text-white py-2 px-4 rounded font-medium hover:bg-red-600 transition-all">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Remove Faculty Modal -->
        <div id="remove-modal" class="fixed inset-0 bg-black bg-opacity-50 items-center justify-center hidden z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-md relative shadow-xl">
                <span class="close absolute top-4 right-4 text-gray-500 cursor-pointer hover:text-gray-700 text-2xl">×</span>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Remove Faculty</h3>
                <p class="text-gray-600 mb-6">Are you sure you want to remove <strong id="remove-modal-faculty-name"></strong> from your department?</p>
                <input type="hidden" id="remove-modal-user-id" name="user_id">
                <div class="flex justify-end space-x-3">
                    <button id="confirm-remove" class="bg-red-600 text-white py-2 px-4 rounded font-medium hover:bg-red-700 transition-all">Confirm</button>
                    <button id="cancel-remove" class="bg-gray-500 text-white py-2 px-4 rounded font-medium hover:bg-gray-600 transition-all">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</body>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>