<?php
// index.php - Professional Directory Registration
session_start();
require_once __DIR__ . '/db.php'; // Uncomment when DB is ready

// Mock DB connection for demo purposes if file doesn't exist
if (!isset($pdo)) {
    try {
        // Create a dummy PDO object or handle connection here
        // $pdo = new PDO("mysql:host=localhost;dbname=test", "root", "");
    } catch (PDOException $e) {
        // die("DB Connection failed");
    }
}

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
$tracking_code = $_SESSION['prof_tracking_code'] ?? null;

// ================= AJAX HANDLER =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_step') {
    
    $step = (int)$_POST['step']; 
    $errors = [];
    $data = [];
    
    $get = fn($k) => isset($_POST[$k]) ? trim($_POST[$k]) : null;

    // --- VALIDATION LOGIC ---
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
        $data['work_experience'] = $get('work_experience');
        $data['education'] = $get('education');
        $data['certifications'] = $get('certifications');
        $data['awards'] = $get('awards');

        if (!$data['work_experience'] || strlen($data['work_experience']) < 50) $errors[] = "Work Experience must be at least 50 characters.";
        if (!$data['education'] || strlen($data['education']) < 50) $errors[] = "Education background must be at least 50 characters.";
    }
    elseif ($step === 6) {
        $data['electronic_signature'] = $get('electronic_signature');
        $data['agreed_terms'] = (isset($_POST['agreed_terms']) && $_POST['agreed_terms'] === '1') ? 1 : 0;

        if (!$data['electronic_signature']) $errors[] = "Electronic Signature is required.";
        if (!$data['agreed_terms']) $errors[] = "You must agree to the Terms of Service.";
    }

    if (!empty($errors)) jsonResponse(['ok' => false, 'errors' => $errors]);

    // --- DB SAVE ---
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
                // GENERATE TRACKING CODE HERE
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
            // Mock ID for demo if no DB
            if($submission_id == 0) {
                $submission_id = 123;
                $tracking_code = 'PRO-' . strtoupper(substr(uniqid(), -6));
                $_SESSION['prof_tracking_code'] = $tracking_code;
            }
        }

        jsonResponse([
            'ok' => true, 
            'submission_id' => $submission_id,
            'step' => $step,
            'tracking_code' => $tracking_code ?? $_SESSION['prof_tracking_code']
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
        --primary: #9333ea; /* Purple Theme */
        --primary-dark: #7e22ce;
        --success: #22c55e;
        --bg: #f8fafc;
        --border: #e2e8f0;
        --text: #334155;
        --text-muted: #94a3b8;
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
    .header-text { text-align: center; margin-bottom: 30px; }
    .header-text h1 { font-size: 26px; font-weight: 700; color: #111827; }
    .header-text p { color: var(--text-muted); margin-top: 5px; font-size: 15px; }

    /* --- PROGRESS BAR STYLING --- */
    .top-progress { 
        height: 10px; 
        background: #e2e8f0; 
        border-radius: 999px; 
        margin: 16px auto 20px; 
        overflow: hidden; 
        max-width: 800px;
    }
    .top-progress .fill { 
        height: 100%; 
        width: 0; 
        background: linear-gradient(90deg, #1f2937, var(--primary)); 
        transition: width .3s ease; 
    }

    .steps { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin: 24px auto 30px; 
        max-width: 850px; 
        padding: 0 10px;
    }
    .step { 
        flex: 1; 
        text-align: center; 
        position: relative; 
        cursor: pointer;
    }
    .bubble {
        width: 55px; 
        height: 55px; 
        border-radius: 50%; 
        background: #fff; 
        border: 2px solid #e2ecff;
        display: inline-flex; 
        align-items: center; 
        justify-content: center; 
        font-weight: 700;
        box-shadow: 0 10px 30px rgba(15,23,42,0.08); 
        transition: all 0.3s;
        font-size: 16px;
        color: var(--text-muted);
    }
    .step .label { 
        margin-top: 8px; 
        color: var(--text-muted); 
        font-size: 13px; 
        font-weight: 500;
    }

    /* Active State (Pops up + Gradient) */
    .step.active .bubble { 
        transform: translateY(-10px); 
        background: linear-gradient(180deg, var(--primary), #7c3aed); 
        color: #fff; 
        border-color: transparent; 
        box-shadow: 0 15px 35px rgba(124, 58, 237, 0.3);
    }
    .step.active .label {
        color: var(--primary);
        font-weight: 700;
    }

    /* Done State (Green) */
    .step.done .bubble { 
        background: var(--success); 
        color: #fff; 
        border-color: transparent; 
        transform: translateY(-6px); 
    }
    .step.done .label {
        color: var(--success);
    }

    /* Form Card */
    .card { background: #fff; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); overflow: hidden; }
    .card-body { padding: 40px; }
    
    .section-header { 
        margin-bottom: 30px; padding: 20px 30px; 
        background: linear-gradient(90deg, #9333ea, #a855f7); 
        color: white; margin: -40px -40px 30px -40px;
    }
    .section-title { font-size: 18px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
    .section-title svg { width: 24px; height: 24px; }
    .section-desc { font-size: 14px; opacity: 0.9; margin-top: 4px; }

    /* Inputs */
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
    .full-width { grid-column: span 2; }
    .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #334155; }
    .form-group input, .form-group select, .form-group textarea {
        width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 14px; background: #f8fafc; transition: border 0.2s;
    }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { 
        outline: none; border-color: var(--primary); background: #fff; box-shadow: 0 0 0 3px rgba(147, 51, 234, 0.1); 
    }
    .required::after { content: " *"; color: #ef4444; }
    .helper-text { font-size: 12px; color: #94a3b8; margin-top: 4px; }

    /* Review Grid */
    .review-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e2e8f0; }
    .review-item h4 { font-size: 12px; color: #64748b; font-weight: 500; margin-bottom: 4px; }
    .review-item p { font-size: 14px; color: #1e293b; font-weight: 600; min-height: 20px; }

    /* Buttons */
    .actions { display: flex; justify-content: space-between; margin-top: 30px; padding-top: 20px; border-top: 1px solid #f1f5f9; }
    .btn { padding: 12px 28px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; font-weight: 600; cursor: pointer; font-size: 14px; transition: 0.2s; }
    .btn:hover { background: #f8fafc; }
    .btn-primary { background: var(--primary); color: #fff; border-color: var(--primary); }
    .btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: 0 4px 6px rgba(147, 51, 234, 0.2); }
    .btn:disabled { opacity: 0.7; cursor: not-allowed; }

    .hidden { display: none !important; }
    .error-msg { background: #fef2f2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; border: 1px solid #fecaca; }

    /* Success & Modal Styles */
    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
    .modal-card { background: #fff; width: 90%; max-width: 500px; padding: 25px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); max-height: 80vh; overflow-y: auto; }
    .modal-title { font-size: 18px; font-weight: 700; margin-bottom: 15px; }
    .modal-text { font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 10px; }
    .success-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.55); display: flex; align-items: center; justify-content: center; z-index: 9999; }
    .success-modal-card { background: #fff; width: 90%; max-width: 500px; padding: 35px 30px; border-radius: 16px; text-align: center; animation: popIn 0.35s ease-out; box-shadow: 0 10px 30px rgba(0,0,0,0.15); }
    .success-check { width: 80px; height: 80px; margin: 0 auto 20px; border-radius: 50%; background: #ecfdf5; display: flex; align-items: center; justify-content: center; color: #059669; font-size: 42px; font-weight: bold; }
    
    .tracking-box { margin: 20px 0; padding: 15px; background: #f3f4f6; border-radius: 8px; border: 1px dashed #9ca3af; }
    .tracking-code { display: block; font-family: monospace; font-size: 18px; font-weight: 700; color: #1f2937; letter-spacing: 1px; margin-top: 5px; }
    
    .next-steps { text-align: left; background: #f9fafb; padding: 15px 20px; border-radius: 8px; margin: 20px 0; }
    .next-steps h4 { margin: 0 0 8px; font-size: 14px; color: #111827; }
    .next-steps ul { margin: 0; padding-left: 20px; color: #4b5563; font-size: 13px; line-height: 1.6; }
    
    @keyframes popIn { from { transform: scale(0.6); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    @media (max-width: 600px) { .grid, .review-grid { grid-template-columns: 1fr; } .steps { display: none; } .top-progress { display:block; } }
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

    <div class="top-progress" role="progressbar">
        <div id="progressBar" class="fill" style="width:0%"></div>
    </div>

    <div class="steps">
        <div class="step active" data-step="1"><div class="bubble">1</div><div class="label">Type&Info</div></div>
        <div class="step" data-step="2"><div class="bubble">2</div><div class="label">Professional</div></div>
        <div class="step" data-step="3"><div class="bubble">3</div><div class="label">Expertise</div></div>
        <div class="step" data-step="4"><div class="bubble">4</div><div class="label">Contact</div></div>
        <div class="step" data-step="5"><div class="bubble">5</div><div class="label">Experience</div></div>
        <div class="step" data-step="6"><div class="bubble">6</div><div class="label">Review</div></div>
    </div>

    <div id="errorBox" class="error-msg hidden"></div>

    <form id="profForm">
        <div class="card">
            <div class="card-body">
                
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
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138z"></path></svg>
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
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            Work Experience, Education & Achievements
                        </div>
                        <div class="section-desc">Provide your work experience, education, and professional achievements</div>
                    </div>

                    <div class="form-group full-width" style="margin-bottom: 20px;">
                        <label class="required">Work Experience</label>
                        <textarea name="work_experience" rows="4" placeholder="Describe your work experience, including previous roles and responsibilities..."></textarea>
                        <div class="helper-text">Minimum 50 characters. This will be displayed on your profile.</div>
                    </div>

                    <div class="form-group full-width" style="margin-bottom: 20px;">
                        <label class="required">Education</label>
                        <textarea name="education" rows="4" placeholder="List your educational background, including degrees and institutions..."></textarea>
                        <div class="helper-text">Minimum 50 characters. This will be displayed on your profile.</div>
                    </div>

                    <div class="form-group full-width" style="margin-bottom: 20px;">
                        <label>Professional Certifications (Optional)</label>
                        <input type="text" name="certifications" placeholder="e.g., NAR, CMA, GRI">
                        <div class="helper-text">Separate multiple certifications with commas</div>
                    </div>

                    <div class="form-group full-width">
                        <label>Awards & Recognitions (Optional)</label>
                        <input type="text" name="awards" placeholder="e.g., Top Agent, Best Service">
                        <div class="helper-text">Separate multiple awards with commas</div>
                    </div>
                </div>

                <div class="panel hidden" data-step="6">
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
                        <div class="review-item"><h4>Professional Bio</h4><p id="revBio" style="font-weight:normal; font-size:13px; text-overflow:ellipsis; overflow:hidden; white-space:nowrap;">-</p></div>
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

<div id="successPopup" class="success-modal hidden">
    <div class="success-modal-card">
        <div class="success-check">✓</div>

        <h2 style="font-size:22px; font-weight:700; margin-bottom:8px;">Registration Submitted!</h2>
        
        <p style="color:#6b7280; margin-bottom:12px; line-height:1.5;">
            Our team will review your application and contact you within <strong>48-72 hours</strong> to complete your profile setup.
        </p>

        <div class="tracking-box">
            Your Tracking ID:
            <span class="tracking-code" id="finalTrackingId">Loading...</span>
        </div>

        <div class="next-steps">
            <h4>What's next:</h4>
            <ul>
                <li>Profile verification and approval</li>
                <li>Access to our client directory</li>
                <li>Marketing and promotional opportunities</li>
                <li>Lead generation tools</li>
            </ul>
        </div>

        <button onclick="window.location.href='/'"
            class="btn btn-primary"
            style="width:100%; margin-top:10px;">
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
    const totalSteps = 6;
    
    const panels = document.querySelectorAll('.panel');
    const bubbles = document.querySelectorAll('.step');
    const progressFill = document.getElementById('progressBar');
    const errorBox = document.getElementById('errorBox');
    
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    
    const form = document.getElementById('profForm');
    
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
        const percent = ((currentStep - 1) / (totalSteps - 1)) * 100;
        progressFill.style.width = percent + '%';
        
        // Buttons
        prevBtn.classList.toggle('hidden', currentStep === 1);
        nextBtn.classList.toggle('hidden', currentStep === totalSteps);
        submitBtn.classList.toggle('hidden', currentStep !== totalSteps);
        
        // Step Bubbles
        bubbles.forEach((b, idx) => {
            const stepNum = idx + 1;
            b.classList.remove('active', 'done');
            
            if (stepNum === currentStep) {
                b.classList.add('active');
            } else if (stepNum < currentStep) {
                b.classList.add('done');
            }
        });
        
        // Populate Summary on Step 6
        if (currentStep === 6) {
            populateReview();
        }

        // Hide error on step change
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
        document.getElementById('revBio').textContent = getVal('bio'); // Changed from Education to Bio
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
                    // Update Tracking ID in Popup
                    if(data.tracking_code) {
                        document.getElementById('finalTrackingId').textContent = data.tracking_code;
                    }
                    
                    // Final Success → Show popup
                    document.getElementById("successPopup").classList.remove("hidden");
                }
            } else {
                errorBox.innerHTML = data.errors.join('<br>');
                errorBox.classList.remove('hidden');
                // Scroll to top to ensure error is seen
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        })
        .catch(err => {
            console.error(err);
            btn.disabled = false;
            btn.textContent = originalText;
            errorBox.textContent = "Network error. Please try again.";
            errorBox.classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
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