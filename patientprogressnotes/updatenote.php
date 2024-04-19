<?php
$sessionAllowWrite = true;
require_once('connection.php');
require_once('lib.php');
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

if ((empty($_GET['token_main'])) && (empty($_GET['term'])) && (empty($_GET['pid']))) {
    // Below functions are from auth.inc, which is included in globals.php
    authCloseSession();
    authLoginScreen(false);
}

$pid = $_GET['pid'];
$ReqId = $_GET['term'];

$db = new sqlDb($conn);

$tableName = 'patient_notes';

if (isset($_POST['submit']) && $_POST['submit'] == "noteupdate") {

    $form_data = $_POST;
    $data = $_POST;

    $data['patient_id'] = $pid;

    $N = $data;

    if ($db->perform($tableName, $N, 'update', "id={$ReqId}")) {
        echo 'Record updated successfully!';
        header("Location:index.php?token_main=" . $_GET['token_main'] . '&set_pid=' . $_GET['pid']);
        exit();
    } else {
        echo "Error: " . $db->error;
    }
} else {
    $sql = "SELECT * FROM $tableName where id = " . $_GET['term'] . "";
    $result = $conn->query($sql);
    $form_data = $result->fetch_assoc();
    // print_r($form_data);exit;    
}



$psql = "SELECT * FROM patient_data where id = $pid";
$presult = $conn->query($psql);

if ($presult->num_rows > 0) {
    // output data of each row
    $patientData = $presult->fetch_assoc();
}

if (isset($patientData['DOB']) && $patientData['DOB'] != "") {

    $patient_dob = "1996-08-15";
    $patient_age = calculateAge($patient_dob);
} else {
    $patient_age = "N/A";
}

function calculateAge($dob)
{
    $dob = new DateTime($dob);
    $today = new DateTime();
    $age = $today->diff($dob);

    return $age->y;
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
            <a class="btn btn-primary" href="index.php?token_main=<?php echo $_GET['token_main'] . '&set_pid=' . $_GET['pid']; ?>">Go Back</a>
        </header>
        <form action="" id="myFormr" method="post" accept-charset="utf-8">

            <div class="mb-3">
                <label for="date_of_service" class="form-label">Date of Service</label>
                <input type="date" name="date_of_service" id="date_of_service" value="<?php echo (isset($form_data['date_of_service']) && $form_data['date_of_service'] != "" ?  $form_data['date_of_service']  : "") ?>" class="form-control" />
            </div>
            
            <div class="mb-3">
                <label for="diagnosis" class="form-label">Diagnosis/ICD 10</label>
                <textarea id="diagnosis" name="diagnosis" class="form-control"><?php echo (isset($form_data['diagnosis']) && $form_data['diagnosis'] != "" ? $form_data['diagnosis'] : "") ?></textarea>
            </div>

            <!-- Personal Information Section -->
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" id="name" value="<?php echo $patientData['title'] . ' ' . $patientData['fname'] . ' ' . $patientData['lname'] ?>" class="form-control" disabled />
            </div>

            <!-- Medical Information Section -->
            <div class="mb-3">
                <label for="dob" class="form-label">Date of Birth</label>
                <input type="text" value="<?php echo (isset($patientData['DOB']) && $patientData['DOB'] != "" ? $patientData['DOB'] : "N/A") ?>" id="dob" class="form-control" disabled />
            </div>

            <div class="mb-3">
                <label for="age" class="form-label">Age</label>
                <input type="text" id="age" value="<?php echo $patient_age ?>" class="form-control" disabled />
            </div>



            <div class="mb-3">
                <label for="precautions" class="form-label">Precautions</label>
                <textarea name="precautions" id="precautions" class="form-control"><?php echo (isset($form_data['precautions']) && $form_data['precautions'] != "" ? $form_data['precautions'] : "") ?></textarea>
            </div>

            <div class="mb-3">
                <label for="falls" class="form-label">Falls/Surgical/Cardiovascular/Other</label>

                <label for="fallsYes">
                    <input type="radio" name="falls" <?php echo (isset($form_data['falls']) && $form_data['falls'] == "Yes" ?  "checked" : "") ?> id="fallsYes" value="Yes"> &nbsp; Yes
                </label>

                <label for="fallsNo">
                    <input type="radio" name="falls" <?php echo (isset($form_data['falls']) && $form_data['falls'] == "No" ?  "checked" : "") ?> id="fallsNo" value="No"> &nbsp; No

                </label>
            </div>

            <div class="mb-3">
                <label for="falls_count" class="form-label">How Many Falls/Result</label>
                <input type="number" name="falls_count" value="<?php echo (isset($form_data['falls_count']) && $form_data['falls_count'] != "" ? $form_data['falls_count'] : "") ?>" id="falls_count" class="form-control" />
            </div>

            <!-- <h3 class="h3 mt-4 mb-2 text-secondary">Insurance/Self-Pay</h3> -->

            <h3 class="h3 text-secondary mt-4 mb-3">Subjective</h3>

            <!-- Medical History -->
            <div class="mb-3">
                <label>Medical History:</label>
                <textarea id="medicalhistory" name="medicalhistory" class="form-control mb-3"><?php echo (isset($form_data['medicalhistory']) && $form_data['medicalhistory'] != "" ? $form_data['medicalhistory'] : "") ?></textarea>
                <label for="medical_history_diabetes"> <input type="checkbox" name="medical_history_diabetes" <?php echo (isset($form_data['medical_history_diabetes']) && $form_data['medical_history_diabetes'] == "Diabetes" ?  "checked" : "") ?> id="medical_history_diabetes" value="Diabetes"> &nbsp; Diabetes</label>

                <label for="medical_history_stroke"> <input type="checkbox" name="medical_history_stroke" <?php echo (isset($form_data['medical_history_stroke']) && $form_data['medical_history_stroke'] == "Stroke" ?  "checked" : "") ?> id="medical_history_stroke" value="Stroke"> &nbsp; Stroke</label>

                <label for="medical_history_tia"> <input type="checkbox" name="medical_history_tia" <?php echo (isset($form_data['medical_history_tia']) && $form_data['medical_history_tia'] == "TIA" ?  "checked" : "") ?> id="medical_history_tia" value="TIA"> &nbsp; TIA (mini stroke)</label>
                <br>
                <!-- Add other medical history checkboxes as needed -->
            </div>

            <div class="mb-3">
                <label>Job Description:</label>
                <textarea id="jobdescription" name="jobdescription" class="form-control mb-3"><?php echo (isset($form_data['jobdescription']) && $form_data['jobdescription'] != "" ? $form_data['jobdescription'] : "") ?></textarea>
            </div>

            <h4 class="h4 text-secondary mt-4 mb-3">Level of Activity</h4>

            <div class="mb-3">
                <label for="activityprior" class="form-label">Prior</label>
                <input type="text" name="activityprior" value="<?php echo (isset($form_data['activityprior']) && $form_data['activityprior'] != "" ? $form_data['activityprior'] : "") ?>" id="activityprior" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="activitycurrent" class="form-label">Current</label>
                <input type="text" name="activitycurrent" value="<?php echo (isset($form_data['activitycurrent']) && $form_data['activitycurrent'] != "" ? $form_data['activitycurrent'] : "") ?>" id="activitycurrent" class="form-control" />
            </div>

            <h4 class="h4 text-secondary mt-4 mb-3">Level of Pain</h4>

            <div class="mb-3">
                <label for="painnumberscale" class="form-label">Number Scale</label>
                <input type="text" name="painnumberscale" value="<?php echo (isset($form_data['painnumberscale']) && $form_data['painnumberscale'] != "" ? $form_data['painnumberscale'] : "") ?>" id="painnumberscale" class="form-control" />
            </div>

            <h4 class="h4 text-secondary mt-4 mb-3">Pain Type</h4>

            <div class="mb-3">
                <label for="acute">
                    <input type="checkbox" name="acute" value="acute" <?php echo (isset($form_data['acute']) && $form_data['acute'] == "acute" ?  "checked" : "") ?> id="acute"> &nbsp; Acute
                </label>

                <label for="chronic">
                    <input type="checkbox" name="chronic" value="chronic" <?php echo (isset($form_data['chronic']) && $form_data['chronic'] == "chronic" ?  "checked" : "") ?> id="chronic"> &nbsp; Chronic
                </label>

                <label for="neuropathic">
                    <input type="checkbox" name="neuropathic" value="neuropathic" <?php echo (isset($form_data['neuropathic']) && $form_data['neuropathic'] == "neuropathic" ?  "checked" : "") ?> id="neuropathic"> &nbsp; Neuropathic
                </label>

                <label for="nociceptive">
                    <input type="checkbox" name="nociceptive" value="nociceptive" <?php echo (isset($form_data['nociceptive']) && $form_data['nociceptive'] == "nociceptive" ?  "checked" : "") ?> id="nociceptive"> &nbsp; Nociceptive
                </label>

                <label for="radicular">
                    <input type="checkbox" name="radicular" value="radicular" <?php echo (isset($form_data['radicular']) && $form_data['radicular'] == "radicular" ?  "checked" : "") ?> id="radicular"> &nbsp; Radicular
                </label>

            </div>
            <h4 class="h4 text-secondary mt-4 mb-3">Location</h4>

            <div class="mb-3">
                <label for="locationleft">
                    <input type="checkbox" name="locationleft" value="locationleft" <?php echo (isset($form_data['locationleft']) && $form_data['locationleft'] == "locationleft" ?  "checked" : "") ?> id="locationleft"> &nbsp; Left
                </label>

                <label for="locationright">
                    <input type="checkbox" name="locationright" value="locationright" <?php echo (isset($form_data['locationright']) && $form_data['locationright'] == "locationright" ?  "checked" : "") ?> id="locationright"> &nbsp; Right
                </label>

                <label for="locationbilateral">
                    <input type="checkbox" name="locationbilateral" value="locationbilateral" <?php echo (isset($form_data['locationbilateral']) && $form_data['locationbilateral'] == "locationbilateral" ?  "checked" : "") ?> id="locationbilateral"> &nbsp; Bilateral
                </label>

            </div>

            <div class="mb-3">
                <label for="bodypart" class="form-label">Body Part/Region</label>
                <textarea id="bodypart" name="bodypart" class="form-control"><?php echo (isset($form_data['bodypart']) && $form_data['bodypart'] != "" ? $form_data['bodypart'] : "") ?></textarea>
            </div>

            <h4 class="h4 text-secondary mt-4 mb-3">Description</h4>
            <div class="mb-3">
                <label for="aggravatedby" class="form-label">Aggravated by</label>
                <textarea id="aggravatedby" name="aggravatedby" class="form-control"><?php echo (isset($form_data['aggravatedby']) && $form_data['aggravatedby'] != "" ? $form_data['aggravatedby'] : "") ?></textarea>
            </div>
            <div class="mb-3">
                <label for="alleviatedby" class="form-label">Alleviated by</label>
                <textarea id="alleviatedby" name="alleviatedby" class="form-control"><?php echo (isset($form_data['alleviatedby']) && $form_data['alleviatedby'] != "" ? $form_data['alleviatedby'] : "") ?></textarea>
            </div>



            <h3 class="h3 text-secondary mt-4 mb-3">Objective</h3>


            <div id="pdfSection">
                <!-- The PDF will be displayed here -->
                <iframe src="images/AROM.pdf" width="100%" height="600px" sandbox="allow-scripts allow-forms"></iframe>
            </div>


            <h4 class="h4 text-secondary mt-4 mb-3">AROM/PROM</h4>

            <div class="mb-3">
                <label for="cervicalflexion" class="form-label">Cervical Flexion</label>
                <input type="text" name="cervicalflexion" value="<?php echo (isset($form_data['cervicalflexion']) && $form_data['cervicalflexion'] != "" ? $form_data['cervicalflexion'] : "") ?>" id="cervicalflexion" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="cervicalextension" class="form-label">Cervical Extension</label>
                <input type="text" name="cervicalextension" value="<?php echo (isset($form_data['cervicalextension']) && $form_data['cervicalextension'] != "" ? $form_data['cervicalextension'] : "") ?>" id="cervicalextension" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="cervicalrotation" class="form-label">Cervical Rotation</label>
                <input type="text" name="cervicalrotation" value="<?php echo (isset($form_data['cervicalrotation']) && $form_data['cervicalrotation'] != "" ? $form_data['cervicalrotation'] : "") ?>" id="cervicalrotation" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="shoulderflexion" class="form-label">Shoulder Flexion</label>
                <input type="text" name="shoulderflexion" value="<?php echo (isset($form_data['shoulderflexion']) && $form_data['shoulderflexion'] != "" ? $form_data['shoulderflexion'] : "") ?>" id="shoulderflexion" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="shoulderextension" class="form-label">Shoulder Extension</label>
                <input type="text" name="shoulderextension" value="<?php echo (isset($form_data['shoulderextension']) && $form_data['shoulderextension'] != "" ? $form_data['shoulderextension'] : "") ?>" id="shoulderextension" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="shoulderir" class="form-label">Shoulder IR</label>
                <input type="text" name="shoulderir" value="<?php echo (isset($form_data['shoulderir']) && $form_data['shoulderir'] != "" ? $form_data['shoulderir'] : "") ?>" id="shoulderir" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="shoulderer" class="form-label">Shoulder ER</label>
                <input type="text" name="shoulderer" value="<?php echo (isset($form_data['shoulderer']) && $form_data['shoulderer'] != "" ? $form_data['shoulderer'] : "") ?>" id="shoulderer" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="elbowflexion" class="form-label">Elbow Flexion</label>
                <input type="text" name="elbowflexion" value="<?php echo (isset($form_data['elbowflexion']) && $form_data['elbowflexion'] != "" ? $form_data['elbowflexion'] : "") ?>" id="elbowflexion" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="elbowextension" class="form-label">Elbow Extension</label>
                <input type="text" name="elbowextension" value="<?php echo (isset($form_data['elbowextension']) && $form_data['elbowextension'] != "" ? $form_data['elbowextension'] : "") ?>" id="elbowextension" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="forearmsupination" class="form-label">Forearm Supination</label>
                <input type="text" name="forearmsupination" value="<?php echo (isset($form_data['forearmsupination']) && $form_data['forearmsupination'] != "" ? $form_data['forearmsupination'] : "") ?>" id="forearmsupination" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="forearmpronation" class="form-label">Forearm Pronation</label>
                <input type="text" name="forearmpronation" value="<?php echo (isset($form_data['forearmpronation']) && $form_data['forearmpronation'] != "" ? $form_data['forearmpronation'] : "") ?>" id="forearmpronation" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="wristflexion" class="form-label">Wrist Flexion</label>
                <input type="text" name="wristflexion" value="<?php echo (isset($form_data['wristflexion']) && $form_data['wristflexion'] != "" ? $form_data['wristflexion'] : "") ?>" id="wristflexion" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="wristextension" class="form-label">Wrist Extension</label>
                <input type="text" name="wristextension" value="<?php echo (isset($form_data['wristextension']) && $form_data['wristextension'] != "" ? $form_data['wristextension'] : "") ?>" id="wristextension" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="trunkflexion" class="form-label">Trunk Flexion</label>
                <input type="text" name="trunkflexion" value="<?php echo (isset($form_data['trunkflexion']) && $form_data['trunkflexion'] != "" ? $form_data['trunkflexion'] : "") ?>" id="trunkflexion" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="trunkextension" class="form-label">Trunk Extension</label>
                <input type="text" name="trunkextension" value="<?php echo (isset($form_data['trunkextension']) && $form_data['trunkextension'] != "" ? $form_data['trunkextension'] : "") ?>" id="trunkextension" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="trunkrotation" class="form-label">Trunk Rotation</label>
                <input type="text" name="trunkrotation" value="<?php echo (isset($form_data['trunkrotation']) && $form_data['trunkrotation'] != "" ? $form_data['trunkrotation'] : "") ?>" id="trunkrotation" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="hipflexion" class="form-label">Hip Flexion</label>
                <input type="text" name="hipflexion" value="<?php echo (isset($form_data['hipflexion']) && $form_data['hipflexion'] != "" ? $form_data['hipflexion'] : "") ?>" id="hipflexion" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="hipextension" class="form-label">Hip Extension</label>
                <input type="text" name="hipextension" value="<?php echo (isset($form_data['hipextension']) && $form_data['hipextension'] != "" ? $form_data['hipextension'] : "") ?>" id="hipextension" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="hipir" class="form-label">Hip IR</label>
                <input type="text" name="hipir" value="<?php echo (isset($form_data['hipir']) && $form_data['hipir'] != "" ? $form_data['hipir'] : "") ?>" id="hipir" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="hiper" class="form-label">Hip ER</label>
                <input type="text" name="hiper" value="<?php echo (isset($form_data['hiper']) && $form_data['hiper'] != "" ? $form_data['hiper'] : "") ?>" id="hiper" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="hipab" class="form-label">Hip Ab</label>
                <input type="text" name="hipab" value="<?php echo (isset($form_data['hipab']) && $form_data['hipab'] != "" ? $form_data['hipab'] : "") ?>" id="hipab" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="hipadd" class="form-label">Hip Add</label>
                <input type="text" name="hipadd" value="<?php echo (isset($form_data['hipadd']) && $form_data['hipadd'] != "" ? $form_data['hipadd'] : "") ?>" id="hipadd" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="kneeflexion" class="form-label">Knee Flexion</label>
                <input type="text" name="kneeflexion" value="<?php echo (isset($form_data['kneeflexion']) && $form_data['kneeflexion'] != "" ? $form_data['kneeflexion'] : "") ?>" id="kneeflexion" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="kneeextension" class="form-label">Knee Extension</label>
                <input type="text" name="kneeextension" value="<?php echo (isset($form_data['kneeextension']) && $form_data['kneeextension'] != "" ? $form_data['kneeextension'] : "") ?>" id="kneeextension" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="ankleplantarflexion" class="form-label">Ankle Plantarflexion</label>
                <input type="text" name="ankleplantarflexion" value="<?php echo (isset($form_data['ankleplantarflexion']) && $form_data['ankleplantarflexion'] != "" ? $form_data['ankleplantarflexion'] : "") ?>" id="ankleplantarflexion" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="ankledorsiflexion" class="form-label">Ankle Dorsiflexion</label>
                <input type="text" name="ankledorsiflexion" value="<?php echo (isset($form_data['ankledorsiflexion']) && $form_data['ankledorsiflexion'] != "" ? $form_data['ankledorsiflexion'] : "") ?>" id="ankledorsiflexion" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="ankleinversion" class="form-label">Ankle Inversion</label>
                <input type="text" name="ankleinversion" value="<?php echo (isset($form_data['ankleinversion']) && $form_data['ankleinversion'] != "" ? $form_data['ankleinversion'] : "") ?>" id="ankleinversion" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="ankleeversion" class="form-label">Ankle Eversion</label>
                <input type="text" name="ankleeversion" value="<?php echo (isset($form_data['ankleeversion']) && $form_data['ankleeversion'] != "" ? $form_data['ankleeversion'] : "") ?>" id="ankleeversion" class="form-control" />
            </div>

            <h4 class="h4 text-secondary mt-4 mb-3">Strength</h4>


            <div class="mb-3">
                <label for="cervicalflexion2" class="form-label">Cervical Flexion</label>
                <input type="text" name="cervicalflexion2" value="<?php echo (isset($form_data['cervicalflexion2']) && $form_data['cervicalflexion2'] != "" ? $form_data['cervicalflexion2'] : "") ?>" id="cervicalflexion2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="cervicalextension2" class="form-label">Cervical Extension</label>
                <input type="text" name="cervicalextension2" value="<?php echo (isset($form_data['cervicalextension2']) && $form_data['cervicalextension2'] != "" ? $form_data['cervicalextension2'] : "") ?>" id="cervicalextension2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="cervicalrotation2" class="form-label">Cervical Rotation</label>
                <input type="text" name="cervicalrotation2" value="<?php echo (isset($form_data['cervicalrotation2']) && $form_data['cervicalrotation2'] != "" ? $form_data['cervicalrotation2'] : "") ?>" id="cervicalrotation2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="shoulderflexion2" class="form-label">Shoulder Flexion</label>
                <input type="text" name="shoulderflexion2" value="<?php echo (isset($form_data['shoulderflexion2']) && $form_data['shoulderflexion2'] != "" ? $form_data['shoulderflexion2'] : "") ?>" id="shoulderflexion2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="shoulderextension2" class="form-label">Shoulder Extension</label>
                <input type="text" name="shoulderextension2" value="<?php echo (isset($form_data['shoulderextension2']) && $form_data['shoulderextension2'] != "" ? $form_data['shoulderextension2'] : "") ?>" id="shoulderextension2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="shoulderir2" class="form-label">Shoulder IR</label>
                <input type="text" name="shoulderir2" value="<?php echo (isset($form_data['shoulderir2']) && $form_data['shoulderir2'] != "" ? $form_data['shoulderir2'] : "") ?>" id="shoulderir2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="shoulderer2" class="form-label">Shoulder ER</label>
                <input type="text" name="shoulderer2" value="<?php echo (isset($form_data['shoulderer2']) && $form_data['shoulderer2'] != "" ? $form_data['shoulderer2'] : "") ?>" id="shoulderer2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="elbowflexion2" class="form-label">Elbow Flexion</label>
                <input type="text" name="elbowflexion2" value="<?php echo (isset($form_data['elbowflexion2']) && $form_data['elbowflexion2'] != "" ? $form_data['elbowflexion2'] : "") ?>" id="elbowflexion2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="elbowextension2" class="form-label">Elbow Extension</label>
                <input type="text" name="elbowextension2" value="<?php echo (isset($form_data['elbowextension2']) && $form_data['elbowextension2'] != "" ? $form_data['elbowextension2'] : "") ?>" id="elbowextension2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="forearmsupination2" class="form-label">Forearm Supination</label>
                <input type="text" name="forearmsupination2" value="<?php echo (isset($form_data['forearmsupination2']) && $form_data['forearmsupination2'] != "" ? $form_data['forearmsupination2'] : "") ?>" id="forearmsupination2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="forearmpronation2" class="form-label">Forearm Pronation</label>
                <input type="text" name="forearmpronation2" value="<?php echo (isset($form_data['forearmpronation2']) && $form_data['forearmpronation2'] != "" ? $form_data['forearmpronation2'] : "") ?>" id="forearmpronation2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="wristflexion2" class="form-label">Wrist Flexion</label>
                <input type="text" name="wristflexion2" value="<?php echo (isset($form_data['wristflexion2']) && $form_data['wristflexion2'] != "" ? $form_data['wristflexion2'] : "") ?>" id="wristflexion2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="wristextension2" class="form-label">Wrist Extension</label>
                <input type="text" name="wristextension2" value="<?php echo (isset($form_data['wristextension2']) && $form_data['wristextension2'] != "" ? $form_data['wristextension2'] : "") ?>" id="wristextension2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="trunkflexion2" class="form-label">Trunk Flexion</label>
                <input type="text" name="trunkflexion2" value="<?php echo (isset($form_data['trunkflexion2']) && $form_data['trunkflexion2'] != "" ? $form_data['trunkflexion2'] : "") ?>" id="trunkflexion2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="trunkextension2" class="form-label">Trunk Extension</label>
                <input type="text" name="trunkextension2" value="<?php echo (isset($form_data['trunkextension2']) && $form_data['trunkextension2'] != "" ? $form_data['trunkextension2'] : "") ?>" id="trunkextension2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="trunkrotation2" class="form-label">Trunk Rotation</label>
                <input type="text" name="trunkrotation2" value="<?php echo (isset($form_data['trunkrotation2']) && $form_data['trunkrotation2'] != "" ? $form_data['trunkrotation2'] : "") ?>" id="trunkrotation2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="hipflexion2" class="form-label">Hip Flexion</label>
                <input type="text" name="hipflexion2" value="<?php echo (isset($form_data['hipflexion2']) && $form_data['hipflexion2'] != "" ? $form_data['hipflexion2'] : "") ?>" id="hipflexion2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="hipextension2" class="form-label">Hip Extension</label>
                <input type="text" name="hipextension2" value="<?php echo (isset($form_data['hipextension2']) && $form_data['hipextension2'] != "" ? $form_data['hipextension2'] : "") ?>" id="hipextension2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="hipir2" class="form-label">Hip IR</label>
                <input type="text" name="hipir2" value="<?php echo (isset($form_data['hipir2']) && $form_data['hipir2'] != "" ? $form_data['hipir2'] : "") ?>" id="hipir2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="hiper2" class="form-label">Hip ER</label>
                <input type="text" name="hiper2" value="<?php echo (isset($form_data['hiper2']) && $form_data['hiper2'] != "" ? $form_data['hiper2'] : "") ?>" id="hiper2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="hipab2" class="form-label">Hip Ab</label>
                <input type="text" name="hipab2" value="<?php echo (isset($form_data['hipab2']) && $form_data['hipab2'] != "" ? $form_data['hipab2'] : "") ?>" id="hipab2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="hipadd2" class="form-label">Hip Add</label>
                <input type="text" name="hipadd2" value="<?php echo (isset($form_data['hipadd2']) && $form_data['hipadd2'] != "" ? $form_data['hipadd2'] : "") ?>" id="hipadd2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="kneeflexion2" class="form-label">Knee Flexion</label>
                <input type="text" name="kneeflexion2" value="<?php echo (isset($form_data['kneeflexion2']) && $form_data['kneeflexion2'] != "" ? $form_data['kneeflexion2'] : "") ?>" id="kneeflexion2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="kneeextension2" class="form-label">Knee Extension</label>
                <input type="text" name="kneeextension2" value="<?php echo (isset($form_data['kneeextension2']) && $form_data['kneeextension2'] != "" ? $form_data['kneeextension2'] : "") ?>" id="kneeextension2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="ankleplantarflexion2" class="form-label">Ankle Plantarflexion</label>
                <input type="text" name="ankleplantarflexion2" value="<?php echo (isset($form_data['ankleplantarflexion2']) && $form_data['ankleplantarflexion2'] != "" ? $form_data['ankleplantarflexion2'] : "") ?>" id="ankleplantarflexion2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="ankledorsiflexion2" class="form-label">Ankle Dorsiflexion</label>
                <input type="text" name="ankledorsiflexion2" value="<?php echo (isset($form_data['ankledorsiflexion2']) && $form_data['ankledorsiflexion2'] != "" ? $form_data['ankledorsiflexion2'] : "") ?>" id="ankledorsiflexion2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="ankleinversion2" class="form-label">Ankle Inversion</label>
                <input type="text" name="ankleinversion2" value="<?php echo (isset($form_data['ankleinversion2']) && $form_data['ankleinversion2'] != "" ? $form_data['ankleinversion2'] : "") ?>" id="ankleinversion2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="ankleeversion2" class="form-label">Ankle Eversion</label>
                <input type="text" name="ankleeversion2" value="<?php echo (isset($form_data['ankleeversion2']) && $form_data['ankleeversion2'] != "" ? $form_data['ankleeversion2'] : "") ?>" id="ankleeversion2" class="form-control" />
            </div>

            <h4 class="h4 text-secondary mt-4 mb-3">Reflexes</h4>

            <div class="mb-3">
                <label for="biceps" class="form-label">Biceps (C5 and C6)</label>
                <input type="text" name="biceps" value="<?php echo (isset($form_data['biceps']) && $form_data['biceps'] != "" ? $form_data['biceps'] : "") ?>" id="biceps" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="radialbrachialis" class="form-label">Radial brachialis (C6)</label>
                <input type="text" name="radialbrachialis" value="<?php echo (isset($form_data['radialbrachialis']) && $form_data['radialbrachialis'] != "" ? $form_data['radialbrachialis'] : "") ?>" id="radialbrachialis" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="triceps" class="form-label">Triceps (C7)</label>
                <input type="text" name="triceps" value="<?php echo (isset($form_data['triceps']) && $form_data['triceps'] != "" ? $form_data['triceps'] : "") ?>" id="triceps" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="distalfingerflexors" class="form-label">Distal finger flexors (C8)</label>
                <input type="text" name="distalfingerflexors" value="<?php echo (isset($form_data['distalfingerflexors']) && $form_data['distalfingerflexors'] != "" ? $form_data['distalfingerflexors'] : "") ?>" id="distalfingerflexors" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="quadricepskneejerk" class="form-label">Quadriceps knee jerk(L4)</label>
                <input type="text" name="quadricepskneejerk" value="<?php echo (isset($form_data['quadricepskneejerk']) && $form_data['quadricepskneejerk'] != "" ? $form_data['quadricepskneejerk'] : "") ?>" id="quadricepskneejerk" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="anklejerk" class="form-label">Ankle jerk(S1)</label>
                <input type="text" name="anklejerk" value="<?php echo (isset($form_data['anklejerk']) && $form_data['anklejerk'] != "" ? $form_data['anklejerk'] : "") ?>" id="anklejerk" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="jawjerk" class="form-label">Jaw Jerk (5th cranial nerve)</label>
                <input type="text" name="jawjerk" value="<?php echo (isset($form_data['jawjerk']) && $form_data['jawjerk'] != "" ? $form_data['jawjerk'] : "") ?>" id="jawjerk" class="form-control" />
            </div>

            <h4 class="h4 text-secondary mt-4 mb-3">Special Test</h4>

            <div class="mb-3">
                <label for="specialtest" class="form-label">Special Test</label>
                <textarea name="specialtest" id="specialtest" class="form-control"><?php echo (isset($form_data['specialtest']) && $form_data['specialtest'] != "" ? $form_data['specialtest'] : "") ?></textarea>
            </div>

            <h4 class="h4 text-secondary mt-4 mb-3">Assessment</h4>

            <div class="mb-3">
                <label for="assessment" class="form-label">Assessment</label>
                <textarea name="assessment" id="assessment" class="form-control"><?php echo (isset($form_data['assessment']) && $form_data['assessment'] != "" ? $form_data['assessment'] : "") ?></textarea>
            </div>

            <!-- Prognosis and Plan Section -->
            <div class="mb-3">
                <label for="prognosis" class="form-label">Prognosis</label>
                <textarea name="prognosis" id="prognosis" class="form-control"><?php echo (isset($form_data['prognosis']) && $form_data['prognosis'] != "" ? $form_data['prognosis'] : "") ?></textarea>
            </div>

            <h4 class="h4 text-secondary mt-4 mb-3">Plan</h4>

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
                <label for="goal_status_1" class="form-label">Goal Status 1</label>
                <input type="text" name="goal_status_1" value="<?php echo (isset($form_data['goal_status_1']) && $form_data['goal_status_1'] != "" ? $form_data['goal_status_1'] : "") ?>" id="goal_status_1" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="goal_status_2" class="form-label">Goal Status 2</label>
                <input type="text" name="goal_status_2" value="<?php echo (isset($form_data['goal_status_2']) && $form_data['goal_status_2'] != "" ? $form_data['goal_status_2'] : "") ?>" id="goal_status_2" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="goal_status_3" class="form-label">Goal Status 3</label>
                <input type="text" name="goal_status_3" value="<?php echo (isset($form_data['goal_status_3']) && $form_data['goal_status_3'] != "" ? $form_data['goal_status_3'] : "") ?>" id="goal_status_3" class="form-control" />
            </div>

            <div class="mb-3">
                <label for="goal_status_4" class="form-label">Goal Status 4</label>
                <input type="text" name="goal_status_4" value="<?php echo (isset($form_data['goal_status_4']) && $form_data['goal_status_4'] != "" ? $form_data['goal_status_4'] : "") ?>" id="goal_status_4" class="form-control" />
            </div>

            <!-- Submit Button -->
            <div class="mb-3">
                <button type="submit" name="submit" value="noteupdate" id="button" class="btn btn-primary">Submit</button>
            </div>
        </form>

    </div>


</body>

</html>