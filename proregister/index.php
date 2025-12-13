<?php
// index.php - Professional Directory Registration
session_start();
require_once __DIR__ . '/db.php'; 

// Reset Logic
if (isset($_GET['new']) && $_GET['new'] === '1') {
    unset($_SESSION['prof_submission_id'], $_SESSION['prof_tracking_code']);
    header("Location: index.php");
    exit;
}

function jsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

$submission_id = $_SESSION['prof_submission_id'] ?? 0;

// ================= AJAX HANDLER =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_step') {
    
    // DB Connection Check
    if (!isset($pdo)) {
        // Fallback for demo purposes if db.php isn't set up yet
        // In production, this would stop execution
        // jsonResponse(['ok' => false, 'errors' => ['Database connection failed']]);
    }

    $step = (int)$_POST['step']; 
    $errors = [];
    $data = [];
    
    $get = fn($k) => isset($_POST[$k]) ? trim($_POST[$k]) : null;

    // --- VALIDATION LOGIC (Kept same as your code) ---
    if ($step === 1) {
        $data['professional_type'] = $get('professional_type');
        $data['first_name'] = $get('first_name');
        $data['last_name'] = $get('last_name');
        $data['email'] = $get('email');
        $data['phone'] = $get('phone');

        if (!$data['professional_type']) $errors[] = "Professional Type is required.";
        if (!$data['first_name'] || !$data['last_name']) $errors[] = "First and Last Name are required.";
        if (!$data['email'] || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "Valid Email is required.";
        if (!$data['phone']) $errors[] = "Phone Number is required.";
    }
    elseif ($step === 2) {
        $data['company_name'] = $get('company_name');
        $data['license_number'] = $get('license_number');
        $data['experience_years'] = $get('experience_years');
        $data['service_areas'] = $get('service_areas');

        if (!$data['company_name']) $errors[] = "Company Name is required.";
        if (!$data['license_number']) $errors[] = "License Number is required.";
        if (!$data['experience_years']) $errors[] = "Years of Experience is required.";
        if (!$data['service_areas']) $errors[] = "Service Areas are required.";
    }
    elseif ($step === 3) {
        $data['specialties'] = $get('specialties');
        $data['certifications'] = $get('certifications');
        $data['languages'] = $get('languages');
        $data['bio'] = $get('bio');

        if (!$data['specialties']) $errors[] = "Specialties are required.";
        if (!$data['bio'] || strlen($data['bio']) < 50) $errors[] = "Bio must be at least 50 characters.";
    }
    elseif ($step === 4) {
        $data['preferred_contact_method'] = $get('preferred_contact_method');
        $data['website_url'] = $get('website_url');
        $data['linkedin_url'] = $get('linkedin_url');

        if (!$data['preferred_contact_method']) $errors[] = "Please select a preferred contact method.";
    }
    elseif ($step === 5) {
        $data['electronic_signature'] = $get('electronic_signature');
        $data['agreed_terms'] = (isset($_POST['agreed_terms']) && $_POST['agreed_terms'] === '1') ? 1 : 0;

        if (!$data['electronic_signature']) $errors[] = "Electronic Signature is required.";
        if (!$data['agreed_terms']) $errors[] = "You must agree to the Terms of Service.";
    }

    if (!empty($errors)) jsonResponse(['ok' => false, 'errors' => $errors]);

    // --- DB SAVE (Only runs if $pdo exists) ---
    try {
        if (isset($pdo)) {
            if ($submission_id > 0) {
                // Update
                $setClauses = [];
                $params = [':id' => $submission_id];
                foreach ($data as $key => $val) {
                    $setClauses[] = "`$key` = :$key";
                    $params[":$key"] = $val;
                }
                $setClauses[] = "last_completed_step = GREATEST(last_completed_step, :step)";
                $params[':step'] = $step;

                $sql = "UPDATE professional_registrations SET " . implode(', ', $setClauses) . " WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            } else {
                // Insert
                $cols = array_keys($data);
                $vals = array_values($data);
                $tracking_code = 'PRO-' . strtoupper(substr(uniqid(), -6));
                
                $placeholders = array_map(fn($c) => ":$c", $cols);
                
                $sql = "INSERT INTO professional_registrations (" . implode(',', $cols) . ", tracking_code, last_completed_step) 
                        VALUES (" . implode(',', $placeholders) . ", :track, :step)";
                
                $params = array_combine($placeholders, $vals);
                $params[':track'] = $tracking_code;
                $params[':step'] = $step;
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                $submission_id = $pdo->lastInsertId();
                $_SESSION['prof_submission_id'] = $submission_id;
                $_SESSION['prof_tracking_code'] = $tracking_code;
            }
        } else {
            // Mock ID for demo if DB not connected
            if($submission_id == 0) $submission_id = 123;
        }

        jsonResponse([
            'ok' => true, 
            'submission_id' => $submission_id,
            'step' => $step
        ]);

    } catch (Exception $e) {
        jsonResponse(['ok' => false, 'errors' => ['DB Error: ' . $e->getMessage()]]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Join Professional Directory</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --primary: #7c3aed; /* Purple Theme */
        --primary-dark: #6d28d9;
        --success: #22c55e;
        --bg: #f3f4f6;
        --border: #e5e7eb;
        --text: #1f2937;
        --text-muted: #6b7280;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); padding-bottom: 60px; }
    
    /* Back Button */
    .back-btn {
        position: absolute; top: 20px; left: 190px;
        background: #fff; border: 1px solid #d1d5db; padding: 8px 20px;
        border-radius: 30px; text-decoration: none; color: var(--text);
        font-weight: 600; font-size: 14px; transition: all 0.2s;
        display: flex; align-items: center; gap: 5px; z-index: 100;
    }
    .back-btn:hover { background: #f9fafb; transform: translateX(-3px); }

    .container { max-width: 900px; margin: 60px auto 40px; padding: 0 20px; }
    
    /* Header */
    .header-text { text-align: center; margin-bottom: 40px; }
    .header-text h1 { font-size: 26px; font-weight: 700; color: #111827; }
    .header-text p { color: var(--text-muted); margin-top: 5px; font-size: 15px; }

    /* --- NEW PROGRESS BAR STYLING --- */
    .progress-wrapper {
        display: flex; justify-content: space-between; position: relative;
        margin-bottom: 50px; max-width: 800px; margin-left: auto; margin-right: auto;
    }
    .progress-track-bg {
        position: absolute; top: 20px; left: 0; width: 100%; height: 4px;
        background: #e5e7eb; z-index: 1; transform: translateY(-50%); border-radius: 4px;
    }
    .progress-fill {
        position: absolute; top: 20px; left: 0; height: 4px;
        background: var(--primary); z-index: 1; transform: translateY(-50%);
        transition: width 0.4s ease; width: 0%; border-radius: 4px;
    }
    .step-item {
        position: relative; z-index: 2; text-align: center; width: 80px; cursor: pointer;
    }
    .step-circle {
        width: 40px; height: 40px; background: #fff; border: 2px solid #e5e7eb;
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        margin: 0 auto 8px; font-weight: 600; color: #9ca3af; transition: all 0.3s;
        font-size: 14px;
    }
    .step-label { font-size: 13px; color: #9ca3af; font-weight: 500; }

    /* Active State */
    .step-item.active .step-circle {
        border-color: var(--primary); background: var(--primary); color: #fff;
        box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.15); transform: scale(1.1);
    }
    .step-item.active .step-label { color: var(--primary); font-weight: 700; }

    /* Completed State */
    .step-item.completed .step-circle {
        background: var(--primary); border-color: var(--primary); color: #fff;
    }
    .step-item.completed .step-label { color: var(--primary); }

    /* Form Card */
    .card { background: #fff; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); overflow: hidden; }
    .card-body { padding: 40px; }
    
    .section-header { 
        margin-bottom: 30px; padding: 20px 30px; 
        background: linear-gradient(90deg, #7c3aed, #8b5cf6); /* Purple Header */
        color: white; margin: -40px -40px 30px -40px;
    }
    .section-title { font-size: 18px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
    .section-title svg { width: 24px; height: 24px; }
    .section-desc { font-size: 14px; opacity: 0.9; margin-top: 4px; }

    /* Inputs */
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
    .full-width { grid-column: span 2; }
    .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #374151; }
    .form-group input, .form-group select, .form-group textarea {
        width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-family: inherit; font-size: 14px; background: #f9fafb; transition: border 0.2s;
    }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { 
        outline: none; border-color: var(--primary); background: #fff; box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1); 
    }
    .required::after { content: " *"; color: #ef4444; }
    .helper-text { font-size: 12px; color: #9ca3af; margin-top: 4px; }

    /* Review Grid */
    .review-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e5e7eb; }
    .review-item h4 { font-size: 12px; color: #6b7280; font-weight: 500; margin-bottom: 4px; }
    .review-item p { font-size: 14px; color: #111827; font-weight: 600; min-height: 20px; }

    /* Buttons */
    .actions { display: flex; justify-content: space-between; margin-top: 30px; padding-top: 20px; border-top: 1px solid #f3f4f6; }
    .btn { padding: 12px 28px; border-radius: 8px; border: 1px solid #d1d5db; background: #fff; font-weight: 600; cursor: pointer; font-size: 14px; transition: 0.2s; }
    .btn:hover { background: #f9fafb; }
    .btn-primary { background: var(--primary); color: #fff; border-color: var(--primary); }
    .btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: 0 4px 6px rgba(124, 58, 237, 0.2); }
    .btn:disabled { opacity: 0.7; cursor: not-allowed; }

    .hidden { display: none !important; }
    .error-msg { background: #fef2f2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; border: 1px solid #fecaca; }

    /* Terms Popup */
    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
    .modal-card { background: #fff; width: 90%; max-width: 500px; padding: 25px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); max-height: 80vh; overflow-y: auto; }
    .modal-title { font-size: 18px; font-weight: 700; margin-bottom: 15px; }
    .modal-text { font-size: 14px; color: #4b5563; line-height: 1.6; margin-bottom: 10px; }
    .modal-btn { width: 100%; background: #111827; color: #fff; padding: 10px; border-radius: 6px; margin-top: 15px; cursor: pointer; border: none; font-weight: 600; }

    /* Success Screen */
    .success-container { text-align: center; padding: 20px; }
    .success-icon { width: 80px; height: 80px; background: #ecfdf5; color: #059669; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; margin: 0 auto 20px; }
    .success-list { text-align: left; background: #f9fafb; padding: 20px; border-radius: 8px; margin: 20px auto; max-width: 500px; border: 1px solid #e5e7eb; }
    .success-list li { margin-bottom: 8px; color: #4b5563; font-size: 14px; list-style-position: inside; }

    @media (max-width: 600px) { .grid, .review-grid { grid-template-columns: 1fr; } .progress-wrapper { display: none; } }
    /* SUCCESS POPUP MODAL */
.success-modal {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.55);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.success-modal-card {
    background: #fff;
    width: 90%;
    max-width: 480px;
    padding: 35px 25px;
    border-radius: 14px;
    text-align: center;
    animation: popIn 0.35s ease-out;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

@keyframes popIn {
    from { transform: scale(0.6); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.success-check {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    border-radius: 50%;
    background: #ecfdf5;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #059669;
    font-size: 42px;
    font-weight: bold;
}

</style>
</head>
<body>

<a href="index.php" class="back-btn">
    <span>←</span> Back to Home
</a>

<div class="container" id="mainFormContainer">
    <div class="header-text">
        <h3>Join Our Professional Directory</h3>
        <p>Register to showcase your expertise and connect with potential clients</p>
    </div>

    <div class="progress-wrapper">
        <div class="progress-track-bg"></div>
        <div class="progress-fill" id="progressBar"></div>
        
        <div class="step-item active" data-step="1">
            <div class="step-circle">1</div>
            <div class="step-label">Info</div>
        </div>
        <div class="step-item" data-step="2">
            <div class="step-circle">2</div>
            <div class="step-label">Details</div>
        </div>
        <div class="step-item" data-step="3">
            <div class="step-circle">3</div>
            <div class="step-label">Expertise</div>
        </div>
        <div class="step-item" data-step="4">
            <div class="step-circle">4</div>
            <div class="step-label">Contact</div>
        </div>
        <div class="step-item" data-step="5">
            <div class="step-circle">5</div>
            <div class="step-label">Review</div>
        </div>
    </div>

    <form id="profForm">
        <div class="card">
            <div class="card-body">
                <div id="errorBox" class="error-msg hidden"></div>

                <div class="panel" data-step="1">
                    <div class="section-header">
                        <div class="section-title">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            Personal Information
                        </div>
                        <div class="section-desc">Tell us about your profession and basic information</div>
                    </div>
                    
                    <div class="form-group full-width" style="margin-bottom:20px;">
                        <label class="required">Professional Type</label>
                        <select name="professional_type">
                            <option value="">Select your profession</option>
                            <option value="Real Estate Agent">Real Estate Agent</option>
                            <option value="loan officer">Loan Officer</option>
                            <option value="Banker">Banker</option>
                            <option value="builder">Builder/Contractor</option>
                            <option value="architect">Architect</option>
                            <option value="inspector">Home Inspector</option>
                        </select>
                    </div>

                    <div class="grid">
                        <div class="form-group">
                            <label class="required">First Name</label>
                            <input type="text" name="first_name" placeholder="John">
                        </div>
                        <div class="form-group">
                            <label class="required">Last Name</label>
                            <input type="text" name="last_name" placeholder="Smith">
                        </div>
                    </div>

                    <div class="form-group full-width" style="margin-bottom: 20px;">
                        <label class="required">Email Address</label>
                        <input type="email" name="email" placeholder="john.smith@email.com">
                    </div>

                    <div class="form-group full-width">
                        <label class="required">Phone Number</label>
                        <input type="tel" name="phone" placeholder="(555) 123-4567">
                    </div>
                </div>

                <div class="panel hidden" data-step="2">
                    <div class="section-header">
                        <div class="section-title">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            Professional Details
                        </div>
                        <div class="section-desc">Share your professional credentials and experience</div>
                    </div>

                    <div class="form-group full-width" style="margin-bottom: 20px;">
                        <label class="required">Company/Brokerage Name</label>
                        <input type="text" name="company_name" placeholder="ABC Real Estate">
                    </div>

                    <div class="form-group full-width" style="margin-bottom: 20px;">
                        <label class="required">License Number</label>
                        <input type="text" name="license_number" placeholder="RE-123456">
                    </div>

                    <div class="form-group full-width" style="margin-bottom: 20px;">
                        <label class="required">Years of Experience</label>
                        <select name="experience_years">
                            <option value="">Select experience</option>
                            <option value="0-2 years">0-2 years</option>
                            <option value="3-5 years">3-5 years</option>
                            <option value="6-10 years">6-10 years</option>
                            <option value="6-10 years">11-15 years</option>
                            <option value="10+ years">16+ years</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label class="required">Service Areas</label>
                        <input type="text" name="service_areas" placeholder="e.g., Manassas Park, Fairfax, Arlington">
                        <div class="helper-text">Separate multiple areas with commas</div>
                    </div>
                </div>

                <div class="panel hidden" data-step="3">
                    <div class="section-header">
                        <div class="section-title">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                            Expertise & Specialties
                        </div>
                        <div class="section-desc">Highlight your expertise and specializations</div>
                    </div>

                    <div class="form-group full-width" style="margin-bottom: 20px;">
                        <label class="required">Specialties</label>
                        <input type="text" name="specialties" placeholder="e.g., First-time buyers, Luxury homes, Investment properties">
                        <div class="helper-text">Separate multiple specialties with commas</div>
                    </div>

                    <div class="form-group full-width" style="margin-bottom: 20px;">
                        <label>Certifications (Optional)</label>
                        <input type="text" name="certifications" placeholder="e.g., CRS, ABR, GRI">
                    </div>

                    <div class="form-group full-width" style="margin-bottom: 20px;">
                        <label>Languages Spoken (Optional)</label>
                        <input type="text" name="languages" placeholder="English, Spanish">
                    </div>

                    <div class="form-group full-width">
                        <label class="required">Professional Biography</label>
                        <textarea name="bio" rows="5" placeholder="Tell potential clients about your experience, approach, and what sets you apart..."></textarea>
                        <div class="helper-text">Minimum 50 characters. This will be displayed on your profile.</div>
                    </div>
                </div>

                <div class="panel hidden" data-step="4">
                    <div class="section-header">
                        <div class="section-title">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                            Contact & Marketing
                        </div>
                        <div class="section-desc">How clients can reach you and find more about you</div>
                    </div>

                    <div class="form-group full-width" style="margin-bottom: 20px;">
                        <label class="required">Preferred Contact Method</label>
                        <select name="preferred_contact_method">
                            <option value="">Select contact method</option>
                            <option value="Email">Email</option>
                            <option value="Phone">Phone</option>
                            <option value="Both">Both phone & Email</option>
                        </select>
                    </div>

                    <div class="form-group full-width" style="margin-bottom: 20px;">
                        <label>Website URL (Optional)</label>
                        <input type="url" name="website_url" placeholder="https://www.yourwebsite.com">
                    </div>

                    <div class="form-group full-width" style="margin-bottom: 20px;">
                        <label>LinkedIn Profile (Optional)</label>
                        <input type="url" name="linkedin_url" placeholder="https://www.linkedin.com/in/yourprofile">
                    </div>

                    <div style="background:#eff6ff; padding:15px; border-radius:6px; font-size:13px; color:#1e3a8a; border: 1px solid #bfdbfe;">
                        <strong>Privacy Note:</strong> Your contact information will only be shared with clients who express interest in your services through our platform.
                    </div>
                </div>

                <div class="panel hidden" data-step="5">
                    <div class="section-header">
                        <div class="section-title">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Review & Submit
                        </div>
                        <div class="section-desc">Review your information and complete registration</div>
                    </div>

                    <div style="font-weight:600; margin-bottom:10px;">Review Your Information</div>
                    <div class="review-grid">
                        <div class="review-item"><h4>Professional Type</h4><p id="revType">-</p></div>
                        <div class="review-item"><h4>Full Name</h4><p id="revName">-</p></div>
                        <div class="review-item"><h4>Email</h4><p id="revEmail">-</p></div>
                        <div class="review-item"><h4>Phone</h4><p id="revPhone">-</p></div>
                        <div class="review-item"><h4>Company</h4><p id="revComp">-</p></div>
                        <div class="review-item"><h4>License Number</h4><p id="revLic">-</p></div>
                        <div class="review-item"><h4>Experience</h4><p id="revExp">-</p></div>
                        <div class="review-item"><h4>Service Areas</h4><p id="revArea">-</p></div>
                    </div>
                    
                    <div class="form-group full-width" style="margin-bottom: 20px;">
                        <label class="required">Electronic Signature</label>
                        <input type="text" name="electronic_signature" placeholder="Type your full name">
                        <div class="helper-text">By typing your name, you are signing this application electronically</div>
                    </div>

                    <div style="margin-top: 20px;">
                        <label style="display:flex; align-items:flex-start; gap:10px; font-size:14px; cursor:pointer;">
                            <input type="checkbox" name="agreed_terms" value="1" style="margin-top:3px;">
                            <span>I agree to the <a href="#" id="openTerms" style="color:var(--primary); font-weight:600;">Terms and Conditions</a> and code of ethics.</span>
                        </label>
                    </div>
                </div>

                <div class="actions">
                    <button type="button" id="prevBtn" class="btn hidden">← Previous</button>
                    <button type="button" id="nextBtn" class="btn btn-primary">Next</button>
                    <button type="button" id="submitBtn" class="btn btn-primary hidden">Submit Registration</button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- SUCCESS POPUP MODAL -->
<div id="successPopup" class="success-modal hidden">
    <div class="success-modal-card">
        <div class="success-check">✓</div>

        <h2 style="font-size:22px; font-weight:700; margin-bottom:8px;">Registration Submitted!</h2>
        <p style="color:#6b7280; margin-bottom:12px;">
            Thank you for joining our professional network.
        </p>

        <p style="color:#6b7280; font-size:14px;">
            Our team will contact you within <strong>48–72 hours</strong> for profile approval.
        </p>

        <button onclick="window.location.href='/'"
            class="btn btn-primary"
            style="width:100%; margin-top:20px;">
            Return to Home
        </button>

        <button onclick="window.location.href='?new=1'"
            class="btn"
            style="width:100%; margin-top:10px;">
            Register Another Professional
        </button>
    </div>
</div>


<div id="termsModal" class="modal-overlay hidden">
    <div class="modal-card">
        <h3 class="modal-title">Terms & Professional Standards</h3>
        <div class="modal-text">
            <strong>1. Professional Conduct</strong><br>
            All registered professionals agree to maintain the highest standards of professional conduct, ethics, and compliance with all applicable local, state, and federal laws and regulations.
        </div>
        <div class="modal-text">
            <strong>2. License & Credentials</strong><br>
            You certify that all licenses, certifications, and credentials provided are current, valid, and accurate. You agree to promptly update any changes to your professional status.
        </div>
        <div class="modal-text">
            <strong>3. Client Relationships</strong><br>
            Professionals are responsible for their own client relationships and transactions. DreamHome Builders acts as a directory service and is not responsible for outcomes of professional services.
        </div>
        <div class="modal-text">
            <strong>4. Data Usage</strong><br>
            Your profile information will be displayed to potential clients. We will not share your personal contact information without your consent.
        </div>
        <div class="modal-text">
            <strong>5. Code of Ethics</strong><br>
            All professionals must adhere to their industry's code of ethics and maintain professional liability insurance as required by law.
        </div>
        <div style="display:flex; gap:10px; margin-top:20px;">
            <button id="closeTermsBtn" class="btn" style="flex:1;">Close</button>
            <button id="acceptTermsBtn" class="btn btn-primary" style="flex:1; background:#000; border-color:#000;">Accept Terms</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    let currentStep = 1;
    const totalSteps = 5;
    
    const panels = document.querySelectorAll('.panel');
    const bubbles = document.querySelectorAll('.step-item');
    const progressFill = document.getElementById('progressBar');
    const errorBox = document.getElementById('errorBox');
    
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    
    const form = document.getElementById('profForm');
    const mainContainer = document.getElementById('mainFormContainer');
    const successScreen = document.getElementById('successScreen');
    
    // Terms Modal Logic
    const openTerms = document.getElementById('openTerms');
    const termsModal = document.getElementById('termsModal');
    const closeTermsBtn = document.getElementById('closeTermsBtn');
    const acceptTermsBtn = document.getElementById('acceptTermsBtn');
    
    openTerms.addEventListener('click', (e) => { e.preventDefault(); termsModal.classList.remove('hidden'); });
    closeTermsBtn.addEventListener('click', () => termsModal.classList.add('hidden'));
    acceptTermsBtn.addEventListener('click', () => { 
        document.querySelector('input[name="agreed_terms"]').checked = true;
        termsModal.classList.add('hidden');
    });

    function updateUI() {
        // Show active panel
        panels.forEach(p => p.classList.toggle('hidden', parseInt(p.dataset.step) !== currentStep));
        
        // Progress Bar Logic (Fill based on current step index)
        // Step 1: 0%, Step 2: 25%, Step 3: 50%, Step 4: 75%, Step 5: 100%
        const percent = ((currentStep - 1) / (totalSteps - 1)) * 100;
        progressFill.style.width = percent + '%';
        
        // Buttons
        prevBtn.classList.toggle('hidden', currentStep === 1);
        nextBtn.classList.toggle('hidden', currentStep === totalSteps);
        submitBtn.classList.toggle('hidden', currentStep !== totalSteps);
        
        // Step Bubbles
        bubbles.forEach((b, idx) => {
            const stepNum = idx + 1;
            b.classList.remove('active', 'completed');
            
            if (stepNum === currentStep) {
                b.classList.add('active');
                b.querySelector('.step-circle').textContent = stepNum;
            } else if (stepNum < currentStep) {
                b.classList.add('completed');
                b.querySelector('.step-circle').innerHTML = '✓'; // Add tick mark
            } else {
                b.querySelector('.step-circle').textContent = stepNum;
            }
        });
        
        // Populate Summary on Step 5
        if (currentStep === 5) {
            populateReview();
        }

        errorBox.classList.add('hidden');
        window.scrollTo(0, 0);
    }
    
    function populateReview() {
        const getVal = name => {
            const el = form.querySelector(`[name="${name}"]`);
            return el ? el.value : '-';
        };
        document.getElementById('revType').textContent = getVal('professional_type');
        document.getElementById('revName').textContent = getVal('first_name') + ' ' + getVal('last_name');
        document.getElementById('revEmail').textContent = getVal('email');
        document.getElementById('revPhone').textContent = getVal('phone');
        document.getElementById('revComp').textContent = getVal('company_name');
        document.getElementById('revLic').textContent = getVal('license_number');
        document.getElementById('revExp').textContent = getVal('experience_years');
        document.getElementById('revArea').textContent = getVal('service_areas');
    }

    function handleSubmission() {
        const formData = new FormData(form);
        formData.append('action', 'save_step');
        formData.append('step', currentStep);

        const btn = (currentStep === totalSteps) ? submitBtn : nextBtn;
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = "Processing...";
        errorBox.classList.add('hidden');

        fetch('', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.textContent = originalText;

            if (data.ok) {
                if (currentStep < totalSteps) {
                    currentStep++;
                    updateUI();
                } else {
                    // Final Success → Show popup
                    document.getElementById("successPopup").classList.remove("hidden");

                }
            } else {
                errorBox.innerHTML = data.errors.join('<br>');
                errorBox.classList.remove('hidden');
                window.scrollTo(0,0);
            }
        })
        .catch(err => {
            console.error(err);
            btn.disabled = false;
            btn.textContent = originalText;
            errorBox.textContent = "Network error. Please try again.";
            errorBox.classList.remove('hidden');
        });
    }

    nextBtn.addEventListener('click', handleSubmission);
    submitBtn.addEventListener('click', handleSubmission);
    prevBtn.addEventListener('click', () => { if(currentStep > 1) { currentStep--; updateUI(); } });
    
    // Allow clicking bubbles to go back
    bubbles.forEach((b, idx) => {
        b.addEventListener('click', () => {
            if (idx + 1 < currentStep) {
                currentStep = idx + 1;
                updateUI();
            }
        });
    });

    updateUI();
});
</script>
</body>
</html>