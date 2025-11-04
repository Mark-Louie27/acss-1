<?php
// Terms and Conditions Modal Content
?>
<div id="termsModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] flex flex-col">
        <!-- Modal Header -->
        <div class="bg-yellow-600 text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
            <h3 class="text-xl font-bold">PRMSU ACSS System - Terms and Conditions</h3>
            <button type="button" onclick="closeTermsModal()" class="text-white hover:text-gray-200 text-2xl">
                ×
            </button>
        </div>

        <!-- Modal Content -->
        <div class="flex-1 overflow-y-auto p-6">
            <div class="prose max-w-none">
                <div class="text-center mb-6">
                    <img src="/assets/logo/main_logo/PRMSUlogo.png" alt="PRMSU Logo" class="w-16 h-16 mx-auto mb-4">
                    <h2 class="text-2xl font-bold text-gray-800">President Ramon Magsaysay State University</h2>
                    <h3 class="text-lg text-gray-600">Automated Class Scheduling System (ACSS)</h3>
                </div>

                <div class="border-b border-gray-200 pb-4 mb-6">
                    <p class="text-sm text-gray-600 text-center">
                        Last Updated: <?= date('F j, Y') ?>
                    </p>
                </div>

                <div class="space-y-6 text-sm">
                    <!-- Acceptance Section -->
                    <section>
                        <h4 class="text-lg font-semibold text-gray-800 mb-3">1. Acceptance of Terms</h4>
                        <p class="text-gray-700 mb-3">
                            By accessing and using the President Ramon Magsaysay State University Automated Class Scheduling System (ACSS),
                            you acknowledge that you have read, understood, and agree to be bound by these Terms and Conditions,
                            all applicable laws and regulations, and the PRMSU Data Privacy Policy.
                        </p>
                        <p class="text-gray-700">
                            If you do not agree with any of these terms, you are prohibited from using or accessing this system.
                        </p>
                    </section>

                    <!-- User Responsibilities -->
                    <section>
                        <h4 class="text-lg font-semibold text-gray-800 mb-3">2. User Responsibilities and Account Security</h4>
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-3">
                            <p class="text-yellow-700 text-sm font-medium">
                                🔒 Account Security is Your Responsibility
                            </p>
                        </div>
                        <ul class="list-disc list-inside space-y-2 text-gray-700">
                            <li>Provide accurate, current, and complete information during registration</li>
                            <li>Maintain the security and confidentiality of your account credentials</li>
                            <li>Notify the system administrator immediately of any unauthorized access</li>
                            <li>Use the system only for authorized academic and administrative purposes</li>
                            <li>Comply with all PRMSU policies, rules, and regulations</li>
                        </ul>
                    </section>

                    <!-- Data Privacy -->
                    <section>
                        <h4 class="text-lg font-semibold text-gray-800 mb-3">3. Data Privacy and Protection</h4>
                        <p class="text-gray-700 mb-3">
                            PRMSU is committed to protecting your privacy and personal data in accordance with
                            the <strong>Data Privacy Act of 2012 (Republic Act No. 10173)</strong> and relevant university policies.
                        </p>
                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-3">
                            <h5 class="font-semibold text-blue-800 mb-2">Data Collection Purpose:</h5>
                            <ul class="list-disc list-inside space-y-1 text-blue-700 text-sm">
                                <li>Academic scheduling and course management</li>
                                <li>Faculty workload management and reporting</li>
                                <li>Institutional planning and accreditation</li>
                                <li>Communication of academic information</li>
                            </ul>
                        </div>
                        <p class="text-gray-700 text-sm">
                            Your personal data will be processed fairly and lawfully. You have the right to access,
                            correct, and object to the processing of your personal data.
                        </p>
                    </section>

                    <!-- Authorized Use -->
                    <section>
                        <h4 class="text-lg font-semibold text-gray-800 mb-3">4. Authorized Use and Prohibited Activities</h4>
                        <p class="text-gray-700 mb-3">
                            The ACSS system is intended for legitimate academic scheduling and management activities.
                            You agree NOT to:
                        </p>
                        <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-3">
                            <ul class="list-disc list-inside space-y-2 text-red-700 text-sm">
                                <li>Access or attempt to access other users' accounts without authorization</li>
                                <li>Tamper with, modify, or delete system data without proper authority</li>
                                <li>Share login credentials with others or use others' credentials</li>
                                <li>Use the system for commercial purposes or personal gain</li>
                                <li>Introduce malicious code or attempt to compromise system security</li>
                                <li>Circumvent system security measures or access controls</li>
                                <li>Engage in any activity that disrupts system performance</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Intellectual Property -->
                    <section>
                        <h4 class="text-lg font-semibold text-gray-800 mb-3">5. Intellectual Property Rights</h4>
                        <p class="text-gray-700">
                            All content, features, and functionality of the ACSS system, including but not limited to
                            text, graphics, logos, and software, are the property of President Ramon Magsaysay State University
                            and are protected by intellectual property laws.
                        </p>
                    </section>

                    <!-- System Availability -->
                    <section>
                        <h4 class="text-lg font-semibold text-gray-800 mb-3">6. System Availability and Maintenance</h4>
                        <p class="text-gray-700 mb-3">
                            PRMSU will make reasonable efforts to ensure the ACSS system is available and functioning properly.
                            However, the university reserves the right to:
                        </p>
                        <ul class="list-disc list-inside space-y-2 text-gray-700">
                            <li>Perform scheduled maintenance which may result in system unavailability</li>
                            <li>Modify, suspend, or discontinue any aspect of the system</li>
                            <li>Limit access to the system for security or administrative reasons</li>
                        </ul>
                    </section>

                    <!-- Liability -->
                    <section>
                        <h4 class="text-lg font-semibold text-gray-800 mb-3">7. Limitation of Liability</h4>
                        <p class="text-gray-700">
                            PRMSU shall not be liable for any indirect, incidental, special, consequential, or punitive damages,
                            including but not limited to loss of data, arising from your use of or inability to use the ACSS system.
                        </p>
                    </section>

                    <!-- Violations -->
                    <section>
                        <h4 class="text-lg font-semibold text-gray-800 mb-3">8. Violations and Sanctions</h4>
                        <p class="text-gray-700 mb-3">
                            Violation of these Terms and Conditions may result in:
                        </p>
                        <ul class="list-disc list-inside space-y-2 text-gray-700">
                            <li>Immediate suspension or termination of system access</li>
                            <li>Disciplinary action in accordance with PRMSU policies</li>
                            <li>Legal action for serious violations</li>
                            <li>Reporting to appropriate authorities when required by law</li>
                        </ul>
                    </section>

                    <!-- Amendments -->
                    <section>
                        <h4 class="text-lg font-semibold text-gray-800 mb-3">9. Amendments to Terms</h4>
                        <p class="text-gray-700">
                            PRMSU reserves the right to modify these Terms and Conditions at any time.
                            Users will be notified of significant changes through official university channels.
                            Continued use of the system after changes constitutes acceptance of the modified terms.
                        </p>
                    </section>

                    <!-- Governing Law -->
                    <section>
                        <h4 class="text-lg font-semibold text-gray-800 mb-3">10. Governing Law and Jurisdiction</h4>
                        <p class="text-gray-700">
                            These Terms and Conditions shall be governed by and construed in accordance with the laws of
                            the Republic of the Philippines. Any disputes shall be subject to the exclusive jurisdiction
                            of the courts of Zambales, Philippines.
                        </p>
                    </section>

                    <!-- Contact Information -->
                    <section class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-lg font-semibold text-gray-800 mb-3">Contact Information</h4>
                        <p class="text-gray-700 mb-2">
                            For questions regarding these Terms and Conditions or the ACSS system, please contact:
                        </p>
                        <div class="text-sm text-gray-600">
                            <p><strong>PRMSU IT Department</strong></p>
                            <p>Email: it-support@prmsu.edu.ph</p>
                            <p>Phone: (047) 811-1234</p>
                            <p>Office Hours: Monday-Friday, 8:00 AM - 5:00 PM</p>
                        </div>
                    </section>
                </div>

                <div class="mt-8 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p class="text-sm text-yellow-800 text-center">
                        <strong>Important:</strong> By accepting these terms, you acknowledge that you have read and understood
                        all provisions and agree to comply with them throughout your use of the ACSS system.
                    </p>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="border-t border-gray-200 px-6 py-4 bg-gray-50 rounded-b-lg">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input type="checkbox" id="acceptTerms" class="h-4 w-4 text-yellow-600 border-gray-300 rounded focus:ring-yellow-500">
                    <label for="acceptTerms" class="ml-2 block text-sm text-gray-700">
                        I have read, understood, and agree to the Terms and Conditions
                    </label>
                </div>
                <div class="flex space-x-3">
                    <button type="button" onclick="closeTermsModal()"
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-yellow-500">
                        Cancel
                    </button>
                    <button type="button" id="confirmTerms" disabled
                        class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-500 focus:outline-none focus:ring-2 focus:ring-yellow-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        onclick="acceptTerms()">
                        Accept & Continue
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function openTermsModal() {
        document.getElementById('termsModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeTermsModal() {
        document.getElementById('termsModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // Enable/disable accept button based on checkbox
    document.getElementById('acceptTerms').addEventListener('change', function() {
        document.getElementById('confirmTerms').disabled = !this.checked;
    });

    function acceptTerms() {
        if (document.getElementById('acceptTerms').checked) {
            // Add hidden input to form
            if (!document.getElementById('terms_accepted')) {
                const termsInput = document.createElement('input');
                termsInput.type = 'hidden';
                termsInput.name = 'terms_accepted';
                termsInput.id = 'terms_accepted';
                termsInput.value = '1';
                document.getElementById('registration-form').appendChild(termsInput);
            }

            // Add timestamp
            if (!document.getElementById('terms_accepted_at')) {
                const timestampInput = document.createElement('input');
                timestampInput.type = 'hidden';
                timestampInput.name = 'terms_accepted_at';
                timestampInput.id = 'terms_accepted_at';
                timestampInput.value = new Date().toISOString();
                document.getElementById('registration-form').appendChild(timestampInput);
            }

            closeTermsModal();

            // Show confirmation
            showTermsAccepted();
        }
    }

    function showTermsAccepted() {
        // You can add a small notification here if needed
        console.log('Terms and conditions accepted');
    }

    // Close modal when clicking outside
    document.getElementById('termsModal').addEventListener('click', function(e) {
        if (e.target.id === 'termsModal') {
            closeTermsModal();
        }
    });

    // Add keyboard escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !document.getElementById('termsModal').classList.contains('hidden')) {
            closeTermsModal();
        }
    });
</script>