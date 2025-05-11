<?php
ob_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Management | ACSS</title>
    <link rel="stylesheet" href="/css/output.css">
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    <style>
        :root {
            --gold: #D4AF37;
            --white: #FFFFFF;
            --gray-dark: #4B5563;
            --gray-light: #E5E7EB;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .slide-in-left {
            animation: slideInLeft 0.5s ease-in;
        }

        @keyframes slideInLeft {
            from {
                transform: translateX(-20px);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .modal {
            transition: opacity 0.3s ease;
        }

        .modal.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .modal-content {
            transition: transform 0.3s ease;
        }

        .input-focus {
            transition: all 0.2s ease;
        }

        .input-focus:focus {
            border-color: var(--gold);
            ring-color: var(--gold);
        }

        .btn-gold {
            background-color: var(--gold);
            color: var(--white);
        }

        .btn-gold:hover {
            background-color: #b8972e;
        }

        .tooltip {
            display: none;
        }

        .group:hover .tooltip {
            display: block;
        }

        .suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--white);
            border: 1px solid var(--gray-light);
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
            background-color: rgba(212, 175, 55, 0.1);
        }

        .loading::after {
            content: '';
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid var(--gold);
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
</head>

<body class="bg-gray-light font-sans antialiased">
    <div id="toast-container" class="fixed top-5 right-5 z-50"></div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Header -->
        <header class="mb-8 slide-in-left">
            <h1 class="text-4xl font-bold text-gray-dark">Faculty Management</h1>
            <p class="text-gray-dark mt-2">Manage faculty members for your department</p>
        </header>

        <!-- Search Bar -->
        <div class="search-container bg-white rounded-xl shadow-lg p-6 mb-6 fade-in">
            <div class="flex items-center bg-gray-50 rounded-full p-3 shadow-inner">
                <i class="fas fa-search text-gray-dark w-5 h-5 mr-3"></i>
                <input type="text" id="search-input" class="search-input flex-1 bg-transparent outline-none text-gray-dark placeholder-gray-dark"
                    placeholder="Search faculty by name..." autocomplete="off" aria-label="Search faculty">
                <span id="search-feedback" class="ml-3 text-sm font-medium"></span>
            </div>
            <div id="suggestions" class="suggestions hidden"></div>
        </div>

        <!-- Search Results -->
        <div id="search-results" class="mb-6"></div>

        <!-- Faculty Table -->
        <div class="bg-white rounded-xl shadow-lg fade-in">
            <div class="flex justify-between items-center p-6 border-b border-gray-light bg-gradient-to-r from-white to-gray-50 rounded-t-xl">
                <h3 class="text-xl font-bold text-gray-dark">Your Department's Faculty</h3>
                <span class="text-sm font-medium text-gray-dark bg-gray-light px-3 py-1 rounded-full"><?php echo count($faculty); ?> Faculty</span>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-light">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Employee ID</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Academic Rank</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Employment Type</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-light">
                            <?php if (empty($faculty)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-dark">
                                        <i class="fas fa-users text-gray-dark text-2xl mb-2"></i>
                                        <p class="text-gray-dark font-medium">No faculty members found in your department</p>
                                        <p class="text-gray-dark text-sm mt-1">Search for faculty to include them</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($faculty as $member): ?>
                                    <tr class="hover:bg-gray-50 transition-all duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-dark"><?php echo htmlspecialchars($member['employee_id']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-dark"><?php echo htmlspecialchars($member['first_name']) . ' ' . htmlspecialchars($member['last_name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-dark"><?php echo htmlspecialchars($member['academic_rank']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-dark"><?php echo htmlspecialchars($member['employment_type']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button class="remove-btn text-red-600 group relative hover:text-red-700 transition-all duration-200"
                                                data-id="<?php echo $member['user_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>">
                                                Remove
                                                <span class="tooltip absolute bg-gray-dark text-white text-xs rounded py-1 px-2 -top-8 left-1/2 transform -translate-x-1/2">Remove Faculty</span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Include Faculty Modal -->
        <div id="include-modal" class="modal fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 transform modal-content scale-95">
                <div class="flex justify-between items-center p-6 border-b border-gray-light bg-gradient-to-r from-white to-gray-50 rounded-t-xl">
                    <h3 class="text-xl font-bold text-gray-dark">Include Faculty</h3>
                    <button id="closeIncludeModalBtn"
                        class="text-gray-dark hover:text-gray-700 focus:outline-none bg-gray-light hover:bg-gray-200 rounded-full h-8 w-8 flex items-center justify-center transition-all duration-200"
                        aria-label="Close modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-6">
                    <p class="text-gray-dark mb-6">Are you sure you want to include <strong id="modal-faculty-name"></strong> in your department?</p>
                    <input type="hidden" id="modal-user-id" name="user_id">
                    <div class="flex justify-end space-x-3">
                        <button id="cancelIncludeBtn" class="bg-gray-light text-gray-dark px-5 py-3 rounded-lg hover:bg-gray-200 transition-all duration-200 font-medium">Cancel</button>
                        <button id="confirmIncludeBtn" class="btn-gold px-5 py-3 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 font-medium">Confirm</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Remove Faculty Modal -->
        <div id="remove-modal" class="modal fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 transform modal-content scale-95">
                <div class="flex justify-between items-center p-6 border-b border-gray-light bg-gradient-to-r from-white to-gray-50 rounded-t-xl">
                    <h3 class="text-xl font-bold text-gray-dark">Remove Faculty</h3>
                    <button id="closeRemoveModalBtn"
                        class="text-gray-dark hover:text-gray-700 focus:outline-none bg-gray-light hover:bg-gray-200 rounded-full h-8 w-8 flex items-center justify-center transition-all duration-200"
                        aria-label="Close modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-6">
                    <p class="text-gray-dark mb-6">Are you sure you want to remove <strong id="remove-modal-faculty-name"></strong> from your department?</p>
                    <input type="hidden" id="remove-modal-user-id" name="user_id">
                    <div class="flex justify-end space-x-3">
                        <button id="cancelRemoveBtn" class="bg-gray-light text-gray-dark px-5 py-3 rounded-lg hover:bg-gray-200 transition-all duration-200 font-medium">Cancel</button>
                        <button id="confirmRemoveBtn" class="bg-red-600 text-white px-5 py-3 rounded-lg hover:bg-red-700 transition-all duration-200 font-medium">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Toast Notifications
            <?php if (isset($success)): ?>
                showToast('<?php echo htmlspecialchars($success); ?>', 'bg-green-500');
            <?php endif; ?>
            <?php if (isset($error)): ?>
                showToast('<?php echo htmlspecialchars($error); ?>', 'bg-red-500');
            <?php endif; ?>

            function showToast(message, bgColor) {
                const toast = document.createElement('div');
                toast.className = `toast ${bgColor} text-white px-4 py-2 rounded-lg shadow-lg`;
                toast.textContent = message;
                toast.setAttribute('role', 'alert');
                document.getElementById('toast-container').appendChild(toast);
                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 300);
                }, 5000);
            }

            // Modal Functions
            function openModal(modalId) {
                const modal = document.getElementById(modalId);
                const modalContent = modal.querySelector('.modal-content');
                modal.classList.remove('hidden');
                modalContent.classList.remove('scale-95');
                modalContent.classList.add('scale-100');
                document.body.style.overflow = 'hidden';
            }

            function closeModal(modalId) {
                const modal = document.getElementById(modalId);
                const modalContent = modal.querySelector('.modal-content');
                modalContent.classList.remove('scale-100');
                modalContent.classList.add('scale-95');
                setTimeout(() => {
                    modal.classList.add('hidden');
                    document.body.style.overflow = 'auto';
                }, 200);
            }

            // Search Functionality
            let searchTimeout;
            const searchInput = document.getElementById('search-input');
            const searchFeedback = document.getElementById('search-feedback');
            const suggestions = document.getElementById('suggestions');
            const searchResults = document.getElementById('search-results');

            searchInput.addEventListener('input', () => {
                const query = searchInput.value.trim();
                clearTimeout(searchTimeout);

                if (query.length < 2) {
                    searchFeedback.textContent = '';
                    suggestions.classList.add('hidden');
                    suggestions.innerHTML = '';
                    renderSearchResults([]);
                    return;
                }

                searchFeedback.textContent = 'Searching...';
                searchFeedback.classList.add('loading', 'text-gray-dark');
                searchFeedback.classList.remove('text-green-500', 'text-red-500');

                searchTimeout = setTimeout(async () => {
                    try {
                        const response = await fetch('/chair/faculty/search', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `name=${encodeURIComponent(query)}`
                        });
                        const data = await response.json();

                        if (data.length > 0) {
                            searchFeedback.textContent = 'Faculty found';
                            searchFeedback.classList.remove('loading', 'text-gray-dark', 'text-red-500');
                            searchFeedback.classList.add('text-green-500');
                            renderSuggestions(data);
                            renderSearchResults(data);
                        } else {
                            searchFeedback.textContent = 'No faculty found';
                            searchFeedback.classList.remove('loading', 'text-gray-dark', 'text-green-500');
                            searchFeedback.classList.add('text-red-500');
                            suggestions.classList.add('hidden');
                            suggestions.innerHTML = '';
                            renderSearchResults([]);
                        }
                    } catch (error) {
                        searchFeedback.textContent = 'Error searching faculty';
                        searchFeedback.classList.remove('loading', 'text-gray-dark', 'text-green-500');
                        searchFeedback.classList.add('text-red-500');
                        suggestions.classList.add('hidden');
                        suggestions.innerHTML = '';
                        renderSearchResults([]);
                    }
                }, 300);
            });

            // Hide suggestions on outside click
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.search-container')) {
                    suggestions.classList.add('hidden');
                }
            });

            // Handle suggestion click
            suggestions.addEventListener('click', async (e) => {
                if (e.target.classList.contains('suggestion-item')) {
                    const name = e.target.textContent;
                    searchInput.value = name;
                    suggestions.classList.add('hidden');
                    searchFeedback.textContent = 'Faculty found';
                    searchFeedback.classList.remove('text-red-500');
                    searchFeedback.classList.add('text-green-500');

                    try {
                        const response = await fetch('/chair/faculty/search', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `name=${encodeURIComponent(name)}`
                        });
                        const data = await response.json();
                        renderSearchResults(data);
                    } catch (error) {
                        console.error('Error:', error);
                    }
                }
            });

            // Render autocomplete suggestions
            function renderSuggestions(results) {
                suggestions.innerHTML = '';
                if (results.length === 0) {
                    suggestions.classList.add('hidden');
                    return;
                }

                results.forEach(result => {
                    const div = document.createElement('div');
                    div.className = 'suggestion-item';
                    div.textContent = `${result.first_name} ${result.last_name} (${result.role_name})`;
                    suggestions.appendChild(div);
                });

                suggestions.classList.remove('hidden');
            }

            // Render search results
            function renderSearchResults(results) {
                searchResults.innerHTML = '';
                if (results.length === 0) {
                    searchResults.innerHTML = '<p class="text-gray-dark text-center py-4">No faculty members found matching your criteria.</p>';
                    return;
                }

                const container = document.createElement('div');
                container.className = 'bg-white rounded-xl shadow-lg p-6';
                container.innerHTML = `
        <div class="flex justify-between items-center border-b border-gray-light pb-2 mb-6">
            <h3 class="text-xl font-bold text-gray-dark">Search Results</h3>
            <span class="text-sm font-medium text-gray-dark bg-gray-light px-3 py-1 rounded-full">${results.length} Found</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-light">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Employee ID</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">College</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Department</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Academic Rank</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Employment Type</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-dark uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-light">
                    ${results.map(result => `
                        <tr class="hover:bg-gray-50 transition-all duration-200">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-dark">${result.employee_id}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-dark">${result.first_name} ${result.last_name}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-dark">
                                ${result.role_name}
                                ${result.dean_college_id ? `(Dean of ${result.college_name})` : ''}
                                ${result.program_name ? `(Chair of ${result.program_name})` : ''}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-dark">${result.college_name || 'N/A'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-dark">${result.department_name || 'N/A'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-dark">${result.academic_rank || 'N/A'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-dark">${result.employment_type || 'N/A'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                ${result.role_name === 'Faculty' ? `
                                    <button class="include-btn text-green-600 group relative hover:text-green-700 transition-all duration-200"
                                        data-id="${result.user_id}"
                                        data-name="${result.first_name} ${result.last_name}">
                                        Include
                                        <span class="tooltip absolute bg-gray-dark text-white text-xs rounded py-1 px-2 -top-8 left-1/2 transform -translate-x-1/2">Include Faculty</span>
                                    </button>
                                ` : ''}
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
                searchResults.appendChild(container);
            }

            // Event Listeners for Include Modal
            document.getElementById('search-results').addEventListener('click', (e) => {
                if (e.target.classList.contains('include-btn')) {
                    const userId = e.target.dataset.id;
                    const facultyName = e.target.dataset.name;
                    document.getElementById('modal-user-id').value = userId;
                    document.getElementById('modal-faculty-name').textContent = facultyName;
                    openModal('include-modal');
                }
            });

            document.getElementById('closeIncludeModalBtn').addEventListener('click', () => closeModal('include-modal'));
            document.getElementById('cancelIncludeBtn').addEventListener('click', () => closeModal('include-modal'));
            document.getElementById('confirmIncludeBtn').addEventListener('click', async () => {
                const userId = document.getElementById('modal-user-id').value;
                const formData = new FormData();
                formData.append('user_id', userId);
                formData.append('add_faculty', '1');

                try {
                    await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    location.reload();
                } catch (error) {
                    showToast('Failed to include faculty. Please try again.', 'bg-red-500');
                }
            });

            // Event Listeners for Remove Modal
            document.querySelector('table').addEventListener('click', (e) => {
                if (e.target.classList.contains('remove-btn')) {
                    const userId = e.target.dataset.id;
                    const facultyName = e.target.dataset.name;
                    document.getElementById('remove-modal-user-id').value = userId;
                    document.getElementById('remove-modal-faculty-name').textContent = facultyName;
                    openModal('remove-modal');
                }
            });

            document.getElementById('closeRemoveModalBtn').addEventListener('click', () => closeModal('remove-modal'));
            document.getElementById('cancelRemoveBtn').addEventListener('click', () => closeModal('remove-modal'));
            document.getElementById('confirmRemoveBtn').addEventListener('click', async () => {
                const userId = document.getElementById('remove-modal-user-id').value;
                const formData = new FormData();
                formData.append('user_id', userId);
                formData.append('remove_faculty', '1');

                try {
                    await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    location.reload();
                } catch (error) {
                    showToast('Failed to remove faculty. Please try again.', 'bg-red-500');
                }
            });

            // Close modals on backdrop click
            ['include-modal', 'remove-modal'].forEach(modalId => {
                const modal = document.getElementById(modalId);
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) closeModal(modalId);
                });
            });

            // Close modals on ESC key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    ['include-modal', 'remove-modal'].forEach(modalId => {
                        const modal = document.getElementById(modalId);
                        if (modal && !modal.classList.contains('hidden')) closeModal(modalId);
                    });
                }
            });
        });
    </script>
</body>

</html>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>