<?php
$sessionAllowWrite = true;
require_once('connection.php');
require_once(__DIR__ . '/../interface/globals.php');
require_once $GLOBALS['srcdir'] . '/ESign/Api.php';

use Esign\Api;
use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Core\Header;
use OpenEMR\Events\Main\Tabs\RenderEvent;
use OpenEMR\Services\LogoService;
use Symfony\Component\Filesystem\Path;

$logoService = new LogoService();
$menuLogo = $logoService->getLogo('core/menu/primary/');

// Ensure token_main matches so this script can not be run by itself
//  If do not match, then destroy the session and go back to login screen

// print_r($_SESSION);exit;

if ((empty($_GET['token_main']))) {
    // Below functions are from auth.inc, which is included in globals.php
    authCloseSession();
    authLoginScreen(false);
}

if (!(isset($_GET['term']) &&  $_GET['term'] != "")) {
    header("Location:index.php?token_main=" . $_GET['token_main'].'&set_pid=' . $_GET['term']);
}

$msg = '';

if (isset($_POST['submit']) && $_POST['submit'] == "intakeupdate") {

    $form_data = $_POST;
    // print_r($form_data);exit;
    // Sample data, replace with actual form data using secure inputs
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    // Add other form fields accordingly...


    $sql = "UPDATE intake_forms SET
        name = '$name',
        email = '$email',
        dob = '" . mysqli_real_escape_string($conn, $_POST['dob']) . "',
        precautions = '" . mysqli_real_escape_string($conn, $_POST['precautions']) . "',
        surgicalhistory = '" . mysqli_real_escape_string($conn, $_POST['surgicalhistory']) . "',
        falls = '" . mysqli_real_escape_string($conn, $_POST['falls']) . "',
        falls_count = '" . intval($_POST['fallsCount']) . "',
        follow_up = '" . mysqli_real_escape_string($conn, $_POST['followUp']) . "',
        level_prior = '" . mysqli_real_escape_string($conn, $_POST['levelPrior']) . "',
        level_current = '" . mysqli_real_escape_string($conn, $_POST['levelCurrent']) . "',
        pain_current = '" . mysqli_real_escape_string($conn, $_POST['painCurrent']) . "',
        pain_worse = '" . mysqli_real_escape_string($conn, $_POST['painWorse']) . "',
        pain_best = '" . mysqli_real_escape_string($conn, $_POST['painBest']) . "',
        assessment = '" . mysqli_real_escape_string($conn, $_POST['assessment']) . "',
        prognosis = '" . mysqli_real_escape_string($conn, $_POST['prognosis']) . "',
        plan = '" . mysqli_real_escape_string($conn, $_POST['plan']) . "',
        shorttermgoal1 = '" . mysqli_real_escape_string($conn, $_POST['shorttermgoal1']) . "',
        shorttermgoal2 = '" . mysqli_real_escape_string($conn, $_POST['shorttermgoal2']) . "',
        shorttermgoal3 = '" . mysqli_real_escape_string($conn, $_POST['shorttermgoal3']) . "',
        shorttermgoal4 = '" . mysqli_real_escape_string($conn, $_POST['shorttermgoal4']) . "',
        longtermgoal1 = '" . mysqli_real_escape_string($conn, $_POST['longtermgoal1']) . "',
        longtermgoal2 = '" . mysqli_real_escape_string($conn, $_POST['longtermgoal2']) . "',
        longtermgoal3 = '" . mysqli_real_escape_string($conn, $_POST['longtermgoal3']) . "',
        longtermgoal4 = '" . mysqli_real_escape_string($conn, $_POST['longtermgoal4']) . "',
        goal_status_1 = '" . mysqli_real_escape_string($conn, $_POST['goalStatus1']) . "',
        goal_status_2 = '" . mysqli_real_escape_string($conn, $_POST['goalStatus2']) . "',
        goal_status_3 = '" . mysqli_real_escape_string($conn, $_POST['goalStatus3']) . "',
        goal_status_4 = '" . mysqli_real_escape_string($conn, $_POST['goalStatus4']) . "',
        height_ft = " . 5 . ",
        height_in = " . 5 . ",
        weight = " . 5 . ",
        body_part_left = '" . (isset($_POST['bodyPartLeft']) ? $_POST['bodyPartLeft'] : "") . "',
        body_part_right = '" . (isset($_POST['bodyPartRight']) ? $_POST['bodyPartRight'] : "") . "',
        problem_begin = '" . mysqli_real_escape_string($conn, $_POST['problemBegin']) . "',
        specific_injury = '" . mysqli_real_escape_string($conn, $_POST['specificInjury']) . "',
        injury_description = '" . mysqli_real_escape_string($conn, $_POST['injuryDescription']) . "',
        previous_pt = '" . (isset($_POST['previousPT']) ? $_POST['previousPT'] : "") . "',
        calendar_year_pt = '" . (isset($_POST['calendarYearPT']) ? $_POST['calendarYearPT'] : "") . "',
        home_care_pt = '" . (isset($_POST['homeCarePT']) ? $_POST['homeCarePT'] : "") . "',
        home_care_discharge = '" . mysqli_real_escape_string($conn, $_POST['homeCareDischarge']) . "',
        work_related_injury = '" . (isset($_POST['workRelatedInjury']) ? $_POST['workRelatedInjury'] : "") . "',
        motor_vehicle_accident = '" . (isset($_POST['motorVehicleAccident']) ? $_POST['motorVehicleAccident'] : "") . "',
        complaint_pain = '" . (isset($_POST['complaintPain']) ? $_POST['complaintPain'] : "") . "',
        complaint_numbness = '" . (isset($_POST['complaintNumbness']) ? $_POST['complaintNumbness'] : "") . "',
        complaint_stiffness = '" . (isset($_POST['complaintStiffness']) ? $_POST['complaintStiffness'] : "") . "',
        complaint_balance_loss = '" . (isset($_POST['complaintBalanceLoss']) ? $_POST['complaintBalanceLoss'] : "") . "',
        complaint_other = '" . (isset($_POST['complaintOther']) ? $_POST['complaintOther'] : "") . "',
        complaint_other_description = '" . mysqli_real_escape_string($conn, $_POST['complaintOtherDescription']) . "',
        pain_rating_current = " . intval($_POST['painCurrent']) . ",
        pain_rating_worst_24_hours = " . intval($_POST['painWorst24Hours']) . ",
        pain_rating_best_24_hours = " . intval($_POST['painBest24Hours']) . ",
        medical_history_diabetes = '" . (isset($_POST['medicalHistoryDiabetes']) ? $_POST['medicalHistoryDiabetes'] : "") . "',
        medical_history_stroke = '" . (isset($_POST['medicalHistoryStroke']) ? $_POST['medicalHistoryStroke'] : "") . "',
        medical_history_tia = '" . (isset($_POST['medicalHistoryTIA']) ? $_POST['medicalHistoryTIA'] : "") . "',
        insuranceselfpay = '" . (isset($_POST['insuranceselfpay']) ? $_POST['insuranceselfpay'] : "") . "',
        insuranceprimary = '" . (isset($_POST['insuranceprimary']) ? $_POST['insuranceprimary'] : "") . "',
        insurancesecondary = '" . (isset($_POST['insurancesecondary']) ? $_POST['insurancesecondary'] : "") . "',
        diagnosis = '" . (isset($_POST['diagnosis']) ? $_POST['diagnosis'] : "") . "',
        medicalhistory = '" . (isset($_POST['medicalhistory']) ? $_POST['medicalhistory'] : "") . "',
        previousphysicaltherapy = '" . (isset($_POST['previousphysicaltherapy']) ? $_POST['previousphysicaltherapy'] : "") . "',
        chiefcomplaint = '" . (isset($_POST['chiefcomplaint']) ? $_POST['chiefcomplaint'] : "") . "',
        socialhistory = '" . (isset($_POST['socialhistory']) ? $_POST['socialhistory'] : "") . "',
        familyhistory = '" . (isset($_POST['familyhistory']) ? $_POST['familyhistory'] : "") . "',
        goalofpatient = '" . (isset($_POST['goalofpatient']) ? $_POST['goalofpatient'] : "") . "',
        arom = '" . (isset($_POST['arom']) ? $_POST['arom'] : "") . "',
        prom = '" . (isset($_POST['prom']) ? $_POST['prom'] : "") . "',
        mmt = '" . (isset($_POST['mmt']) ? $_POST['mmt'] : "") . "',
        posture = '" . (isset($_POST['posture']) ? $_POST['posture'] : "") . "',
        balance = '" . (isset($_POST['balance']) ? $_POST['balance'] : "") . "',
        cane = '" . (isset($_POST['cane']) ? $_POST['cane'] : "") . "',
        walker = '" . (isset($_POST['walker']) ? $_POST['walker'] : "") . "',
        wheelchair = '" . (isset($_POST['wheelchair']) ? $_POST['wheelchair'] : "") . "',
        otherdevice = '" . (isset($_POST['otherdevice']) ? $_POST['otherdevice'] : "") . "',
        otherdevicename = '" . (isset($_POST['otherdevicename']) ? $_POST['otherdevicename'] : "") . "'
    WHERE id = " . $_GET['term'];

    if ($conn->query($sql) === TRUE) {
        unset($form_data);
        $msg = "Data updated successfully!";
        header("Location:index.php?token_main=" . $_GET['token_main']. '&set_pid=' . $_GET['term']);
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
        $msg = "Error: " . $sql . "<br>" . $conn->error;
    }

    // Close connection
    $conn->close();
} else {
    $sql = "SELECT * FROM intake_forms where id = " . $_GET['term'] . "";
    $result = $conn->query($sql);
    $form_data = $result->fetch_assoc();
    // print_r($form_data);exit;    
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Physical Therapy Progress Note | Motion Sync EMR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/all.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="images/logo.jpg">
</head>

<body>
    <div class="container">
        <header>
            <img class="logo" src="images/logo.jpg" alt="logo">
            <h1 id="title" class="text-center">Physical Therapy Progress Note</h1>
            <p id="description" class="text-center description">Motion Sync EMR</p>
            <a class="btn btn-primary" href="index.php?token_main=<?php echo $_GET['token_main'].'&set_pid=' . $_GET['term']; ?>">Go Back</a>
        </header>
        <form action="" id="myFormr" method="post" accept-charset="utf-8">
            <input type="hidden" name="FormrID" value="myFormr">

            <!-- Personal Information Section -->
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" name="name" id="name" value="<?php echo (isset($form_data['name']) && $form_data['name'] != "" ? $form_data['name'] : "") ?>" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" value="<?php echo (isset($form_data['email']) && $form_data['email'] != "" ? $form_data['email'] : "") ?>" class="form-control" />
            </div>

            <!-- Medical Information Section -->
            <div class="mb-3">
                <label for="dob" class="form-label">Date of Birth</label>
                <input type="date" name="dob" value="<?php echo (isset($form_data['dob']) && $form_data['dob'] != "" ? $form_data['dob'] : "") ?>" id="dob" class="form-control" />
            </div>

            <!-- <h3 class="h3 mt-4 mb-2 text-secondary">Insurance/Self-Pay</h3> -->

            <div class="mb-3">
                <label for="insuranceselfpay" class="form-label">Insurance/Self-Pay</label>
                <textarea id="insuranceselfpay" name="insuranceselfpay" class="form-control"><?php echo (isset($form_data['insuranceselfpay']) && $form_data['insuranceselfpay'] != "" ? $form_data['insuranceselfpay'] : "") ?></textarea>
            </div>
            <div class="mb-3">
                <label for="insuranceprimary" class="form-label">Insurance Primary</label>
                <textarea id="insuranceprimary" name="insuranceprimary" class="form-control"><?php echo (isset($form_data['insuranceprimary']) && $form_data['insuranceprimary'] != "" ? $form_data['insuranceprimary'] : "") ?></textarea>
            </div>
            <div class="mb-3">
                <label for="insurancesecondary" class="form-label">Insurance Secondary</label>
                <textarea id="insurancesecondary" name="insurancesecondary" class="form-control"><?php echo (isset($form_data['insurancesecondary']) && $form_data['insurancesecondary'] != "" ? $form_data['insurancesecondary'] : "") ?></textarea>
            </div>

            <div class="mb-3">
                <label for="diagnosis" class="form-label">Diagnosis</label>
                <textarea id="diagnosis" name="diagnosis" class="form-control"><?php echo (isset($form_data['diagnosis']) && $form_data['diagnosis'] != "" ? $form_data['diagnosis'] : "") ?></textarea>
            </div>

            <!-- Medical History -->
            <div class="mb-3">
                <label>Medical History:</label>
                <textarea id="medicalhistory" name="medicalhistory" class="form-control mb-3"><?php echo (isset($form_data['medicalhistory']) && $form_data['medicalhistory'] != "" ? $form_data['medicalhistory'] : "") ?></textarea>
                <label for="medicalHistoryDiabetes"> <input type="checkbox" name="medicalHistoryDiabetes" <?php echo (isset($form_data['medical_history_diabetes']) && $form_data['medical_history_diabetes'] == "Diabetes" ?  "checked" : "") ?> id="medicalHistoryDiabetes" value="Diabetes"> &nbsp; Diabetes</label>

                <label for="medicalHistoryStroke"> <input type="checkbox" name="medicalHistoryStroke" <?php echo (isset($form_data['medical_history_stroke']) && $form_data['medical_history_stroke'] == "Stroke" ?  "checked" : "") ?> id="medicalHistoryStroke" value="Stroke"> &nbsp; Stroke</label>

                <label for="medicalHistoryTIA"> <input type="checkbox" name="medicalHistoryTIA" <?php echo (isset($form_data['medical_history_tia']) && $form_data['medical_history_tia'] == "TIA" ?  "checked" : "") ?> id="medicalHistoryTIA" value="TIA"> &nbsp; TIA (mini stroke)</label>
                <br>
                <!-- Add other medical history checkboxes as needed -->
            </div>

            <div class="mb-3">
                <label for="previousphysicaltherapy" class="form-label">Previous Physical Therapy</label>
                <textarea name="previousphysicaltherapy" id="previousphysicaltherapy" class="form-control"><?php echo (isset($form_data['previousphysicaltherapy']) && $form_data['previousphysicaltherapy'] != "" ? $form_data['previousphysicaltherapy'] : "") ?></textarea>
            </div>

            <h3 class="h3 text-secondary mt-4 mb-3">Subjective</h3>

            <div class="mb-3">
                <label for="precautions" class="form-label">Precautions</label>
                <textarea name="precautions" id="precautions" class="form-control"><?php echo (isset($form_data['precautions']) && $form_data['precautions'] != "" ? $form_data['precautions'] : "") ?></textarea>
            </div>

            <div class="mb-3">
                <label for="chiefcomplaint" class="form-label">Chief Complaint</label>
                <textarea name="chiefcomplaint" id="chiefcomplaint" class="form-control"><?php echo (isset($form_data['chiefcomplaint']) && $form_data['chiefcomplaint'] != "" ? $form_data['chiefcomplaint'] : "") ?></textarea>
            </div>

            <!-- Injury Details -->
            <!-- Injury Details -->
            <div class="mb-3">
                <label>Is your injury work-related?</label>

                <label for="workRelatedInjuryYes"> <input type="radio" name="workRelatedInjury" <?php echo (isset($form_data['work_related_injury']) && $form_data['work_related_injury'] == "Yes" ?  "checked" : "") ?> id="workRelatedInjuryYes" value="Yes"> &nbsp; Yes</label>

                <label for="workRelatedInjuryNo"> <input type="radio" name="workRelatedInjury" <?php echo (isset($form_data['work_related_injury']) && $form_data['work_related_injury'] == "No" ?  "checked" : "") ?> id="workRelatedInjuryNo" value="No"> &nbsp; No</label>
            </div>

            <div class="mb-3">
                <label>Is your injury related to a motor vehicle accident?</label>

                <label for="motorVehicleAccidentYes"> <input type="radio" name="motorVehicleAccident" <?php echo (isset($form_data['motor_vehicle_accident']) && $form_data['motor_vehicle_accident'] == "Yes" ?  "checked" : "") ?> id="motorVehicleAccidentYes" value="Yes"> &nbsp; Yes</label>

                <label for="motorVehicleAccidentNo"> <input type="radio" name="motorVehicleAccident" <?php echo (isset($form_data['motor_vehicle_accident']) && $form_data['motor_vehicle_accident'] == "No" ?  "checked" : "") ?> id="motorVehicleAccidentNo" value="No"> &nbsp; No</label>
            </div>



            <!-- Subjective Section -->
            <div class="mb-3">
                <label for="surgicalhistory" class="form-label">Surgical History</label>
                <textarea name="surgicalhistory" id="surgicalhistory" class="form-control"><?php echo (isset($form_data['surgicalhistory']) && $form_data['surgicalhistory'] != "" ? $form_data['surgicalhistory'] : "") ?></textarea>
            </div>

            <div class="mb-3">
                <label for="socialhistory" class="form-label">Social History</label>
                <textarea name="socialhistory" id="socialhistory" class="form-control"><?php echo (isset($form_data['socialhistory']) && $form_data['socialhistory'] != "" ? $form_data['socialhistory'] : "") ?></textarea>
            </div>

            <div class="mb-3">
                <label for="familyhistory" class="form-label">Family History</label>
                <textarea name="familyhistory" id="familyhistory" class="form-control"><?php echo (isset($form_data['familyhistory']) && $form_data['familyhistory'] != "" ? $form_data['familyhistory'] : "") ?></textarea>
            </div>

            <div class="mb-3">
                <label for="goalofpatient" class="form-label">Goal of the Patient</label>
                <textarea name="goalofpatient" id="goalofpatient" class="form-control"><?php echo (isset($form_data['goalofpatient']) && $form_data['goalofpatient'] != "" ? $form_data['goalofpatient'] : "") ?></textarea>
            </div>

            <div class="mb-3">
                <label for="falls" class="form-label">Falls in the previous year</label>

                <label for="fallsYes">
                    <input type="radio" name="falls" <?php echo (isset($form_data['falls']) && $form_data['falls'] == "Yes" ?  "checked" : "") ?> id="fallsYes" value="Yes"> &nbsp; Yes
                </label>

                <label for="fallsNo">
                    <input type="radio" name="falls" <?php echo (isset($form_data['falls']) && $form_data['falls'] == "No" ?  "checked" : "") ?> id="fallsNo" value="No"> &nbsp; No

                </label>
            </div>

            <div class="mb-3">
                <label for="fallsCount" class="form-label">How Many Falls/Result</label>
                <input type="number" name="fallsCount" value="<?php echo (isset($form_data['falls_count']) && $form_data['falls_count'] != "" ? $form_data['falls_count'] : "") ?>" id="fallsCount" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="followUp" class="form-label">Required follow up?</label>
                <input type="text" name="followUp" value="<?php echo (isset($form_data['follow_up']) && $form_data['follow_up'] != "" ? $form_data['follow_up'] : "") ?>" id="followUp" class="form-control" />
            </div>

            <!-- Level of Function Section -->
            <div class="mb-3">
                <label for="levelPrior" class="form-label">Level of Function (Prior)</label>
                <input type="text" name="levelPrior" value="<?php echo (isset($form_data['level_prior']) && $form_data['level_prior'] != "" ? $form_data['level_prior'] : "") ?>" id="levelPrior" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="levelCurrent" class="form-label">Level of Function (Current)</label>
                <input type="text" name="levelCurrent" value="<?php echo (isset($form_data['level_current']) && $form_data['level_current'] != "" ? $form_data['level_current'] : "") ?>" id="levelCurrent" class="form-control" />
            </div>

            <!-- Pain Section -->
            <div class="mb-3">
                <label for="painCurrent" class="form-label">Pain (Current)</label>
                <input type="text" name="painCurrent" value="<?php echo (isset($form_data['pain_current']) && $form_data['pain_current'] != "" ? $form_data['pain_current'] : "") ?>" id="painCurrent" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="painBest" class="form-label">Pain (Best)</label>
                <input type="text" name="painBest" value="<?php echo (isset($form_data['pain_best']) && $form_data['pain_best'] != "" ? $form_data['pain_best'] : "") ?>" id="painBest" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="painWorse" class="form-label">Pain (Worse)</label>
                <input type="text" name="painWorse" value="<?php echo (isset($form_data['pain_worse']) && $form_data['pain_worse'] != "" ? $form_data['pain_worse'] : "") ?>" id="painWorse" class="form-control" />
            </div>



            <div class="mb-3">
                <label for="paresthesia" class="form-label">Paresthesia</label>
                <input type="text" name="paresthesia" value="<?php echo (isset($form_data['paresthesia']) && $form_data['paresthesia'] != "" ? $form_data['paresthesia'] : "") ?>" id="paresthesia" class="form-control" />
            </div>

            <h3 class="h3 text-secondary mt-4 mb-3">Objective</h3>
            <!-- Objective Section -->
            <!-- Add fields for Range of Motion, Discoloration, Wounds -->
            <div id="pdfSection">
                <!-- The PDF will be displayed here -->
                <iframe src="images/AROM.pdf" width="100%" height="600px" sandbox="allow-scripts allow-forms"></iframe>
            </div>
            <!-- Assessment Section -->
            <div class="mb-3">
                <label for="arom" class="form-label">AROM</label>
                <input type="text" name="arom" value="<?php echo (isset($form_data['arom']) && $form_data['arom'] != "" ? $form_data['arom'] : "") ?>" id="arom" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="prom" class="form-label">PROM</label>
                <input type="text" name="prom" value="<?php echo (isset($form_data['prom']) && $form_data['prom'] != "" ? $form_data['prom'] : "") ?>" id="prom" class="form-control" />
            </div>

            <div id="mmtSection" class="text-center">
                <!-- The PDF will be displayed here -->
                <img src="images/mmt.jpeg" class="img-fluid" />
            </div>

            <div class="mb-3">
                <label for="mmt" class="form-label">MMT/Strength</label>
                <input type="text" name="mmt" value="<?php echo (isset($form_data['mmt']) && $form_data['mmt'] != "" ? $form_data['mmt'] : "") ?>" id="mmt" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="posture" class="form-label">Posture</label>
                <input type="text" name="posture" value="<?php echo (isset($form_data['posture']) && $form_data['posture'] != "" ? $form_data['posture'] : "") ?>" id="posture" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="balance" class="form-label">Balance</label>
                <input type="text" name="balance" value="<?php echo (isset($form_data['balance']) && $form_data['balance'] != "" ? $form_data['balance'] : "") ?>" id="balance" class="form-control" />
            </div>



            <div class="mb-3">
                <label>Assistive Devices</label>

                <label for="cane">
                    <input type="checkbox" name="cane" value="cane" <?php echo (isset($form_data['cane']) && $form_data['cane'] == "cane" ?  "checked" : "") ?> id="cane"> &nbsp; Cane
                </label>

                <label for="walker">
                    <input type="checkbox" name="walker" value="walker" <?php echo (isset($form_data['walker']) && $form_data['walker'] == "walker" ?  "checked" : "") ?> id="walker"> &nbsp; Walker
                </label>

                <label for="wheelchair">
                    <input type="checkbox" name="wheelchair" value="wheelchair" <?php echo (isset($form_data['wheelchair']) && $form_data['wheelchair'] == "wheelchair" ?  "checked" : "") ?> id="wheelchair"> &nbsp; Wheelchair
                </label>

                <label for="otherdevice">
                    <input type="checkbox" name="otherdevice" value="otherdevice" <?php echo (isset($form_data['otherdevice']) && $form_data['otherdevice'] == "otherdevice" ?  "checked" : "") ?> id="other"> &nbsp; Other
                </label>

                <input type="text" name="otherdevicename" value="<?php echo (isset($form_data['otherdevicename']) && $form_data['otherdevicename'] != "" ? $form_data['otherdevicename'] : "") ?>" id="otherdevicename" class="form-control" />

            </div>

            <div class="mb-3">
                <label for="assessment" class="form-label">Assessment</label>
                <textarea name="assessment" id="assessment" class="form-control"><?php echo (isset($form_data['assessment']) && $form_data['assessment'] != "" ? $form_data['assessment'] : "") ?></textarea>
            </div>

            <!-- Prognosis and Plan Section -->
            <div class="mb-3">
                <label for="prognosis" class="form-label">Prognosis</label>
                <textarea name="prognosis" id="prognosis" class="form-control"><?php echo (isset($form_data['prognosis']) && $form_data['prognosis'] != "" ? $form_data['prognosis'] : "") ?></textarea>
            </div>

            <div class="mb-3">
                <label for="plan" class="form-label">Plan</label>
                <textarea name="plan" id="plan" class="form-control"><?php echo (isset($form_data['plan']) && $form_data['plan'] != "" ? $form_data['plan'] : "") ?></textarea>
            </div>

            <!-- Goals Section -->
            <div class="mb-3">
                <label for="shortTermGoals" class="form-label">Short Term Goals</label>
            </div>

            <div class="input-group mb-3">
                <span class="input-group-text" id="shorttermgoal1">1</span>
                <input type="text" name="shorttermgoal1" value="<?php echo (isset($form_data['shorttermgoal1']) && $form_data['shorttermgoal1'] != "" ? $form_data['shorttermgoal1'] : "") ?>" id="shorttermgoal1" class="form-control">
            </div>

            <div class="input-group mb-3">
                <span class="input-group-text" id="shorttermgoal2">2</span>
                <input type="text" name="shorttermgoal2" value="<?php echo (isset($form_data['shorttermgoal2']) && $form_data['shorttermgoal2'] != "" ? $form_data['shorttermgoal2'] : "") ?>" id="shorttermgoal2" class="form-control">
            </div>

            <div class="input-group mb-3">
                <span class="input-group-text" id="shorttermgoal3">3</span>
                <input type="text" name="shorttermgoal3" value="<?php echo (isset($form_data['shorttermgoal3']) && $form_data['shorttermgoal3'] != "" ? $form_data['shorttermgoal3'] : "") ?>" id="shorttermgoal3" class="form-control">
            </div>

            <div class="input-group mb-3">
                <span class="input-group-text" id="shorttermgoal4">4</span>
                <input type="text" name="shorttermgoal4" value="<?php echo (isset($form_data['shorttermgoal4']) && $form_data['shorttermgoal4'] != "" ? $form_data['shorttermgoal4'] : "") ?>" id="shorttermgoal4" class="form-control">
            </div>



            <div class="mb-3">
                <label for="longTermGoals" class="form-label">Long Term Goals</label>

            </div>

            <div class="input-group mb-3">
                <span class="input-group-text" id="longtermgoal1">1</span>
                <input type="text" name="longtermgoal1" value="<?php echo (isset($form_data['longtermgoal1']) && $form_data['longtermgoal1'] != "" ? $form_data['longtermgoal1'] : "") ?>" id="longtermgoal1" class="form-control">
            </div>

            <div class="input-group mb-3">
                <span class="input-group-text" id="longtermgoal2">2</span>
                <input type="text" name="longtermgoal2" value="<?php echo (isset($form_data['longtermgoal2']) && $form_data['longtermgoal2'] != "" ? $form_data['longtermgoal2'] : "") ?>" id="longtermgoal2" class="form-control">
            </div>

            <div class="input-group mb-3">
                <span class="input-group-text" id="longtermgoal3">3</span>
                <input type="text" name="longtermgoal3" value="<?php echo (isset($form_data['longtermgoal3']) && $form_data['longtermgoal3'] != "" ? $form_data['longtermgoal3'] : "") ?>" id="longtermgoal3" class="form-control">
            </div>

            <div class="input-group mb-3">
                <span class="input-group-text" id="longtermgoal4">4</span>
                <input type="text" name="longtermgoal4" value="<?php echo (isset($form_data['longtermgoal4']) && $form_data['longtermgoal4'] != "" ? $form_data['longtermgoal4'] : "") ?>" id="longtermgoal4" class="form-control">
            </div>

            <!-- Goal Status Section -->
            <div class="mb-3">
                <label for="goalStatus1" class="form-label">Goal Status 1</label>
                <input type="text" name="goalStatus1" value="<?php echo (isset($form_data['goalStatus1']) && $form_data['goalStatus1'] != "" ? $form_data['goalStatus1'] : "") ?>" id="goalStatus1" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="goalStatus2" class="form-label">Goal Status 2</label>
                <input type="text" name="goalStatus2" value="<?php echo (isset($form_data['goalStatus2']) && $form_data['goalStatus2'] != "" ? $form_data['goalStatus2'] : "") ?>" id="goalStatus2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="goalStatus3" class="form-label">Goal Status 3</label>
                <input type="text" name="goalStatus3" value="<?php echo (isset($form_data['goalStatus3']) && $form_data['goalStatus3'] != "" ? $form_data['goalStatus3'] : "") ?>" id="goalStatus3" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="goalStatus4" class="form-label">Goal Status 4</label>
                <input type="text" name="goalStatus4" value="<?php echo (isset($form_data['goalStatus4']) && $form_data['goalStatus4'] != "" ? $form_data['goalStatus4'] : "") ?>" id="goalStatus4" class="form-control" />
            </div>
          

            <!-- Submit Button -->
            <div class="mb-3">
                <button type="submit" name="submit" value="intakeupdate" id="button" class="btn btn-primary">Submit</button>
            </div>
        </form>


    </div>


</body>

</html>