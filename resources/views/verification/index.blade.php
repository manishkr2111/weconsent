<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KYC Verification | Linkodart</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9f9f9;
            padding: 40px;
            text-align: center;
        }
        .container {
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        #sumsub-websdk {
            margin-top: 20px;
            min-height: 600px;
        }
        button {
            background-color: #3A86FF;
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            margin: 8px;
        }
        button:disabled { 
            opacity: 0.6; 
            cursor: not-allowed;
        }
        .status-section { 
            margin-top: 30px; 
        }
        .status-badge { 
            display: inline-block; 
            padding: 8px 16px; 
            border-radius: 20px; 
            font-weight: 600; 
            font-size: 14px; 
        }
        .status-pending { background-color: #FFD700; color: #000; }
        .status-completed { background-color: #28a745; color: #fff; }
        .status-rejected { background-color: #dc3545; color: #fff; }
        .error-message {
            color: #dc3545;
            margin-top: 10px;
            padding: 10px;
            background-color: #f8d7da;
            border-radius: 8px;
            display: none;
        }
        .success-message {
            color: #28a745;
            margin-top: 10px;
            padding: 10px;
            background-color: #d4edda;
            border-radius: 8px;
            display: none;
        }
        .form-section {
            margin-top: 20px;
            text-align: left;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Identity Verification</h2>
    <p>Please complete your KYC verification process.</p>

    <div id="errorMessage" class="error-message"></div>
    <div id="successMessage" class="success-message"></div>

    <!-- Step 1: Create Applicant Form -->
    <div id="applicantForm" class="form-section">
        <h3>Step 1: Enter Your Details</h3>
        <div class="form-group">
            <label>First Name</label>
            <input type="text" id="firstName" placeholder="Enter your first name" required>
        </div>
        <div class="form-group">
            <label>Last Name</label>
            <input type="text" id="lastName" placeholder="Enter your last name" required>
        </div>
        <button id="createApplicantBtn">Create Applicant & Start Verification</button>
    </div>

    <!-- Step 2: SDK Container -->
    <div id="sdkSection" style="display:none;">
        <h3>Step 2: Complete Verification</h3>
        <div id="sumsub-websdk"></div>
    </div>

    <!-- Step 3: Status Check -->
    <div class="status-section" id="statusSection" style="display:none;">
        <h3>Verification Status</h3>
        <span id="statusBadge" class="status-badge status-pending">Loading...</span>
        <button id="checkStatusBtn">Refresh Status</button>
    </div>
</div>

<script>
    const createBtn = document.getElementById('createApplicantBtn');
    const statusBtn = document.getElementById('checkStatusBtn');
    const tokenUrl = "{{ route('sumsub.token') }}";
    const applicantUrl = "/sumsub/applicant";
    const userId = "{{ auth()->user()->id }}";
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    let applicantId = null;
    let snsWebSdkInstance = null;
    let sdkLoaded = false;
    let sdkLoadPromise = null;

    // Load Sumsub SDK with Promise
    function loadSumsubSDK() {
        if (sdkLoadPromise) return sdkLoadPromise;
        
        sdkLoadPromise = new Promise((resolve, reject) => {
            // Check if already loaded (lowercase snsWebSdk is the correct global)
            if (typeof snsWebSdk !== 'undefined') {
                sdkLoaded = true;
                resolve();
                return;
            }

            const sdkScript = document.createElement('script');
            sdkScript.src = "https://static.sumsub.com/idensic/static/sns-websdk-builder.js";
            sdkScript.async = true;
            
            sdkScript.onload = () => {
                console.log("Sumsub SDK script loaded");
                // Give it a moment to initialize
                setTimeout(() => {
                    if (typeof snsWebSdk !== 'undefined') {
                        sdkLoaded = true;
                        console.log("snsWebSdk is now available");
                        resolve();
                    } else {
                        reject(new Error('SDK loaded but snsWebSdk is not defined'));
                    }
                }, 100);
            };
            
            sdkScript.onerror = () => {
                reject(new Error('Failed to load Sumsub SDK script'));
            };
            
            document.head.appendChild(sdkScript);
        });
        
        return sdkLoadPromise;
    }

    // Start loading SDK immediately
    loadSumsubSDK().catch(err => console.error('SDK preload error:', err));

    function showError(message) {
        const errorEl = document.getElementById('errorMessage');
        errorEl.textContent = message;
        errorEl.style.display = 'block';
        setTimeout(() => errorEl.style.display = 'none', 5000);
    }

    function showSuccess(message) {
        const successEl = document.getElementById('successMessage');
        successEl.textContent = message;
        successEl.style.display = 'block';
        setTimeout(() => successEl.style.display = 'none', 5000);
    }

    // Step 1: Create Applicant
    createBtn.addEventListener('click', async () => {
        const firstName = document.getElementById('firstName').value.trim();
        const lastName = document.getElementById('lastName').value.trim();

        if (!firstName || !lastName) {
            showError('Please enter both first name and last name');
            return;
        }

        createBtn.disabled = true;
        createBtn.innerText = "Creating Applicant...";

        try {
            // Create applicant first
            const applicantResponse = await fetch(applicantUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Authorization': 'Bearer ' + localStorage.getItem('api_token')
                },
                body: JSON.stringify({ firstName, lastName })
            });

            const applicantData = await applicantResponse.json();
            
            if (!applicantResponse.ok) {
                throw new Error(applicantData.message || 'Failed to create applicant');
            }

            applicantId = applicantData.id;
            showSuccess('Applicant created successfully! Loading verification...');
            
            // Hide form, show SDK section
            document.getElementById('applicantForm').style.display = 'none';
            document.getElementById('sdkSection').style.display = 'block';

            // Now get the access token and initialize SDK
            await initializeVerification();

        } catch (error) {
            console.error('Error creating applicant:', error);
            showError(error.message || 'Failed to create applicant. Please try again.');
            createBtn.disabled = false;
            createBtn.innerText = "Create Applicant & Start Verification";
        }
    });

    async function initializeVerification() {
        try {
            // Ensure SDK is loaded
            console.log('Loading SDK...');
            await loadSumsubSDK();
            console.log('SDK loaded, fetching token...');

            // Get access token
            const tokenResponse = await fetch(tokenUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            const tokenData = await tokenResponse.json();
            
            if (!tokenData || !tokenData.token) {
                throw new Error("Access token not received");
            }

            console.log('Token received, initializing SDK...');
            
            // Double check snsWebSdk is available (LOWERCASE!)
            if (typeof snsWebSdk === 'undefined') {
                throw new Error("snsWebSdk is still not defined after loading");
            }

            // Initialize SDK
            initializeSumsubSDK(tokenData.token);

        } catch (error) {
            console.error('Error initializing verification:', error);
            showError(error.message || 'Failed to initialize verification');
            document.getElementById('applicantForm').style.display = 'block';
            document.getElementById('sdkSection').style.display = 'none';
            createBtn.disabled = false;
            createBtn.innerText = "Create Applicant & Start Verification";
        }
    }

    function initializeSumsubSDK(accessToken) {
        try {
            console.log('Building Sumsub SDK instance...');
            console.log('Checking if snsWebSdk is available:', typeof snsWebSdk);
            
            // Verify snsWebSdk exists (LOWERCASE!)
            if (typeof snsWebSdk === 'undefined') {
                console.error('snsWebSdk is not available in global scope');
                throw new Error('snsWebSdk is not available in global scope');
            }

            console.log('snsWebSdk found, initializing...');

            snsWebSdkInstance = snsWebSdk.init(
                accessToken,
                // Token expiration handler
                () => {
                    console.log('Token expired, fetching new token...');
                    return fetch(tokenUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (!data.token) {
                            throw new Error('No token in response');
                        }
                        console.log('New token received');
                        return data.token;
                    });
                }
            )
            .withConf({
                lang: 'en',
                uiConf: {
                    customCssUrl: null
                }
            })
            .withOptions({ 
                addViewportTag: false, 
                adaptIframeHeight: true
            })
            .on('idCheck.onReady', () => {
                console.log('Sumsub SDK ready');
                showSuccess('Verification form loaded. Please complete all steps.');
            })
            .on('idCheck.onError', (error) => {
                console.error('Sumsub SDK error:', error);
                showError('Verification error: ' + (error.message || JSON.stringify(error)));
            })
            .on('idCheck.onStepCompleted', (payload) => {
                console.log('Step completed:', payload);
            })
            .on('idCheck.applicantSubmitted', (payload) => {
                console.log('Applicant submitted:', payload);
                showSuccess('Verification submitted successfully!');
                document.getElementById('statusSection').style.display = 'block';
                setTimeout(() => checkStatus(), 2000);
            })
            .on('idCheck.onApplicantLoaded', (payload) => {
                console.log('Applicant loaded:', payload);
            })
            .on('idCheck.onApplicantResubmitted', (payload) => {
                console.log('Applicant resubmitted:', payload);
                showSuccess('Verification resubmitted successfully!');
            })
            .build();

            console.log('SDK built, launching...');
            // Launch the SDK
            snsWebSdkInstance.launch('#sumsub-websdk');
            console.log('SDK launched');

        } catch (error) {
            console.error('Error building/launching SDK:', error);
            showError('Failed to initialize verification form: ' + error.message);
            throw error;
        }
    }

    // Check Status Function
    async function checkStatus() {
        if (!applicantId) {
            showError('No applicant ID found');
            return;
        }

        statusBtn.disabled = true;
        statusBtn.innerText = "Checking...";
        document.getElementById('statusSection').style.display = 'block';

        try {
            const response = await fetch(`/sumsub/applicant/${applicantId}/status`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + localStorage.getItem('api_token')
                }
            });

            const data = await response.json();
            const statusBadge = document.getElementById('statusBadge');
            const reviewStatus = data.reviewStatus || data.data?.reviewStatus || "unknown";
            //console.log('reviewStatus',reviewStatus);

            statusBadge.className = "status-badge";

            switch(reviewStatus.toLowerCase()) {
                case 'pending':
                case 'init':
                    statusBadge.classList.add('status-pending');
                    statusBadge.innerText = 'Pending Review';
                    break;
                case 'completed':
                case 'approved':
                    statusBadge.classList.add('status-completed');
                    statusBadge.innerText = 'Approved';
                    break;
                case 'rejected':
                    statusBadge.classList.add('status-rejected');
                    statusBadge.innerText = 'Rejected';
                    break;
                default:
                    statusBadge.classList.add('status-pending');
                    statusBadge.innerText = 'Status: ' + reviewStatus;
            }
        } catch (error) {
            console.error('Status check error:', error);
            showError('Failed to check status');
        } finally {
            statusBtn.disabled = false;
            statusBtn.innerText = "Refresh Status";
        }
    }

    statusBtn.addEventListener('click', checkStatus);
</script>
</body>
</html>