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

if ((empty($_GET['token_main'])) && (empty($_GET['term'])) && (empty($_GET['pid']))) {
    // Below functions are from auth.inc, which is included in globals.php
    authCloseSession();
    authLoginScreen(false);
}

$pid  = $_GET['pid'];

if (isset($_GET['term']) &&  $_GET['term'] != "") {
    // SQL query to select all data
    $sql = "SELECT * FROM patient_notes where id = " . $_GET['term'] . "";
    $result = $conn->query($sql);
} else {
    header("Location:index.php?token_main=" . $_GET['token_main'] . '&set_pid=' . $_GET['pid']);
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
    <title>Progress Note | Motion Sync EMR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/all.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="images/logo.jpg">
</head>

<body>

    <div class="container">
        <header>
            <img class="logo" src="images/logo.jpg" alt="logo">
            <h1 class="intake-title" class="text-center">Physical Therapy Progress Note</h1>
            <p id="description" class="text-center description">Motion Sync EMR</p>
            <a class="btn btn-primary" href="index.php?token_main=<?php echo $_GET['token_main'] . '&set_pid=' . $_GET['pid']; ?>">Go Back</a>
            <button id="downloadBtn" class='btn btn-success'> <i class='fa-solid fa-download'></i> Download PDF</button> <button class='btn btn-secondary btn-print'><i class='fa-solid fa-print'></i> Print</button>
        </header>

        <?php
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $dateString =  $row['date_of_service'];
            $americanFormat = date("m/d/Y", strtotime($dateString));

        ?>

            <div class="card container-print" id="pdfSection">
                <div class="card-body p-5">
                    <h4 class="card-title">Progress Note (<?php echo $patientData['title'] . ' ' . $patientData['fname'] . ' ' . $patientData['lname'] ?>)</h4>

                    <div class="row view-intake-forms-rows">

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Date of Service</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($americanFormat) && $americanFormat != "") ? $americanFormat  : "N/A"; ?></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Name</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo $patientData['title'] . ' ' . $patientData['fname'] . ' ' . $patientData['lname'] ?></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Email</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($patientData['email_direct']) && $patientData['email_direct'] != "") ? $patientData['email_direct']  : "N/A"; ?></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Date of Birth</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($patientData['DOB']) && $patientData['DOB'] != "" ?  date("m/d/Y", strtotime($patientData['DOB'])) : "N/A") ?></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Age</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($patient_age) && $patient_age != "") ? $patient_age  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Diagnosis/ICD 10</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['diagnosis']) && $row['diagnosis'] != "") ? $row['diagnosis']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Precautions:</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['precautions']) && $row['precautions'] != "") ? $row['precautions']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Falls/Surgical/Cardiovascular/Other</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['falls']) && $row['falls'] != "") ? $row['falls']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>How Many Falls/Result</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['falls_count']) && $row['falls_count'] != "") ? $row['falls_count']  : "N/A"; ?></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-12">
                            <h3 class="h3 text-center text-secondary mt-4 mb-3">Subjective</h3>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Medical History</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['medicalhistory']) && $row['medicalhistory'] != "") ? $row['medicalhistory']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Medical History Diabetes</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['medical_history_diabetes']) && $row['medical_history_diabetes'] != "") ? $row['medical_history_diabetes']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Medical History Stroke</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['medical_history_stroke']) && $row['medical_history_stroke'] != "") ? $row['medical_history_stroke']  : "N/A"; ?></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Medical History TIA (mini stroke)</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['medical_history_tia']) && $row['medical_history_tia'] != "") ? $row['medical_history_tia']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Job Description</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['jobdescription']) && $row['jobdescription'] != "") ? $row['jobdescription']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-12">
                            <h4 class="h4 text-secondary mt-4 mb-3">Level of Activity</h4>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Prior</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['activityprior']) && $row['activityprior'] != "") ? $row['activityprior']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Current</strong></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['activitycurrent']) && $row['activitycurrent'] != "") ? $row['activitycurrent']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-12">
                            <h4 class="h4 text-secondary mt-4 mb-3">Level of Pain</h4>
                        </div>


                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Number Scale</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['painnumberscale']) && $row['painnumberscale'] != "") ? $row['painnumberscale']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-12">
                            <h4 class="h4 text-secondary mt-4 mb-3">Pain Type</h4>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Acute</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['acute']) && $row['acute'] == "acute") ? "YES"  : "NO"; ?></p>
                        </div>


                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Chronic</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['chronic']) && $row['chronic'] == "chronic") ? "YES"  : "NO"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Neuropathic</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['neuropathic']) && $row['neuropathic'] == "neuropathic") ? "YES"  : "NO"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Nociceptive</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['nociceptive']) && $row['nociceptive'] == "nociceptive") ? "YES"  : "NO"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Radicular</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['radicular']) && $row['radicular'] == "radicular") ?  "YES"  : "NO"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-12">
                            <h4 class="h4 text-secondary mt-4 mb-3">Location</h4>
                        </div>



                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Left</strong></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['locationleft']) && $row['locationleft'] == "locationleft" ?  "YES" : "NO") ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Right</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['locationright']) && $row['locationright'] == "locationright" ?  "YES" : "NO") ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Bilateral</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['locationbilateral']) && $row['locationbilateral'] == "locationbilateral") ?  "YES" : "NO"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Body Part/Region</strong></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['bodypart']) && $row['bodypart'] != "") ? $row['bodypart']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-12">
                            <h4 class="h4 text-secondary mt-4 mb-3">Description</h4>
                        </div>



                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Aggravated by</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['aggravatedby']) && $row['aggravatedby'] != "") ? $row['aggravatedby']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Alleviated by</strong></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['alleviatedby']) && $row['alleviatedby'] != "") ? $row['alleviatedby']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-12">
                            <h3 class="h3 text-center text-secondary mt-4 mb-3">Objective</h3>
                        </div>
                        <div class="col-12 col-sm-12 col-md-12">
                            <h6 class="text-secondary mt-4 mb-3">AROM/PROM</h6>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Cervical Flexion</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['cervicalflexion']) && $row['cervicalflexion'] != "") ? $row['cervicalflexion']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Cervical Extension</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['cervicalextension']) && $row['cervicalextension'] != "") ? $row['cervicalextension']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Cervical Rotation</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['cervicalrotation']) && $row['cervicalrotation'] != "") ? $row['cervicalrotation']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Shoulder Flexion</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['shoulderflexion']) && $row['shoulderflexion'] != "") ? $row['shoulderflexion']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Shoulder Extension</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['shoulderextension']) && $row['shoulderextension'] != "") ? $row['shoulderextension']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Shoulder IR</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['shoulderir']) && $row['shoulderir'] != "") ? $row['shoulderir']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Shoulder ER</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['shoulderer']) && $row['shoulderer'] != "") ? $row['shoulderer']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Elbow Flexion</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['elbowflexion']) && $row['elbowflexion'] != "") ? $row['elbowflexion']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Elbow Extension</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['elbowextension']) && $row['elbowextension'] != "") ? $row['elbowextension']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Forearm Supination</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['forearmsupination']) && $row['forearmsupination'] != "") ? $row['forearmsupination']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Forearm Pronation</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['forearmpronation']) && $row['forearmpronation'] != "") ? $row['forearmpronation']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Wrist Flexion</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['wristflexion']) && $row['wristflexion'] != "") ? $row['wristflexion']  : "N/A"; ?></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Wrist Extension</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['wristextension']) && $row['wristextension'] != "") ? $row['wristextension']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Trunk Flexion</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['trunkflexion']) && $row['trunkflexion'] != "") ? $row['trunkflexion']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Trunk Extension</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['trunkextension']) && $row['trunkextension'] != "") ? $row['trunkextension']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Trunk Rotation</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['trunkrotation']) && $row['trunkrotation'] != "") ? $row['trunkrotation']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Hip Flexion</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['hipflexion']) && $row['hipflexion'] != "") ? $row['hipflexion']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Hip Extension</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['hipextension']) && $row['hipextension'] != "") ? $row['hipextension']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Hip IR</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['hipir']) && $row['hipir'] != "") ? $row['hipir']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Hip ER</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['hiper']) && $row['hiper'] != "") ? $row['hiper']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Hip Ab</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['hipab']) && $row['hipab'] != "") ? $row['hipab']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Hip Add</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['hipadd']) && $row['hipadd'] != "") ? $row['hipadd']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Knee Flexion</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['kneeflexion']) && $row['kneeflexion'] != "") ? $row['kneeflexion']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Knee Extension</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['kneeextension']) && $row['kneeextension'] != "") ? $row['kneeextension']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Ankle Plantarflexion</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['ankleplantarflexion']) && $row['ankleplantarflexion'] != "") ? $row['ankleplantarflexion']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Ankle Dorsiflexion</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['ankleinversion']) && $row['ankleinversion'] != "") ? $row['ankleinversion']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Ankle Eversion</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['ankleeversion']) && $row['ankleeversion'] != "") ? $row['ankleeversion']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-12">
                            <h4 class="h4 text-secondary mt-4 mb-3">Strength</h4>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Cervical Flexion</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['cervicalflexion2']) && $row['cervicalflexion2'] != "") ? $row['cervicalflexion2']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Cervical Extension</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['cervicalextension2']) && $row['cervicalextension2'] != "") ? $row['cervicalextension2']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Cervical Rotation</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['cervicalrotation2']) && $row['cervicalrotation2'] != "") ? $row['cervicalrotation2']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Shoulder Flexion</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['shoulderflexion2']) && $row['shoulderflexion2'] != "") ? $row['shoulderflexion2']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Shoulder Extension</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['shoulderextension2']) && $row['shoulderextension2'] != "") ? $row['shoulderextension2']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Shoulder IR</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['shoulderir2']) && $row['shoulderir2'] != "") ? $row['shoulderir2']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Shoulder ER</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['shoulderer2']) && $row['shoulderer2'] != "") ? $row['shoulderer2']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Elbow Flexion</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['elbowflexion2']) && $row['elbowflexion2'] != "") ? $row['elbowflexion2']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Elbow Extension</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['elbowextension2']) && $row['elbowextension2'] != "") ? $row['elbowextension2']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Forearm Supination</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['forearmsupination2']) && $row['forearmsupination2'] != "") ? $row['forearmsupination2']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Forearm Pronation</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['forearmpronation2']) && $row['forearmpronation2'] != "") ? $row['forearmpronation2']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Wrist Flexion</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['wristflexion2']) && $row['wristflexion2'] != "") ? $row['wristflexion2']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Wrist Extension</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['wristextension2']) && $row['wristextension2'] != "") ? $row['wristextension2']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Trunk Flexion</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['trunkflexion2']) && $row['trunkflexion2'] != "") ? $row['trunkflexion2']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Trunk Extension</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['trunkextension2']) && $row['trunkextension2'] != "") ? $row['trunkextension2']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Trunk Rotation</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['trunkrotation2']) && $row['trunkrotation2'] != "") ? $row['trunkrotation2']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Hip Flexion</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['hipflexion2']) && $row['hipflexion2'] != "") ? $row['hipflexion2']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Hip Extension</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['hipextension2']) && $row['hipextension2'] != "") ? $row['hipextension2']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Hip IR</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['hipir2']) && $row['hipir2'] != "") ? $row['hipir2']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Hip ER</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['hiper2']) && $row['hiper2'] != "") ? $row['hiper2']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Hip Ab</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['hipab2']) && $row['hipab2'] != "") ? $row['hipab2']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Hip Add</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['hipadd2']) && $row['hipadd2'] != "") ? $row['hipadd2']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Knee Flexion</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['kneeflexion2']) && $row['kneeflexion2'] != "") ? $row['kneeflexion2']  : "N/A"; ?></p>
                        </div>
                        
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Knee Extension</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['kneeextension2']) && $row['kneeextension2'] != "") ? $row['kneeextension2']  : "N/A"; ?></p>
                        </div>
                        
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Ankle Plantarflexion</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['ankleplantarflexion2']) && $row['ankleplantarflexion2'] != "") ? $row['ankleplantarflexion2']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Ankle Dorsiflexion</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['ankledorsiflexion2']) && $row['ankledorsiflexion2'] != "") ? $row['ankledorsiflexion2']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Ankle Inversion</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['ankleinversion2']) && $row['ankleinversion2'] != "") ? $row['ankleinversion2']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Ankle Eversion</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['ankleeversion2']) && $row['ankleeversion2'] != "") ? $row['ankleeversion2']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-12">
                            <h4 class="h4 text-secondary mt-4 mb-3">Reflexes</h4>
                        </div>

                        
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Biceps (C5 and C6)</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['biceps']) && $row['biceps'] != "") ? $row['biceps']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Radial brachialis (C6)</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['radialbrachialis']) && $row['radialbrachialis'] != "") ? $row['radialbrachialis']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Triceps (C7)</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['triceps']) && $row['triceps'] != "") ? $row['triceps']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Distal finger flexors (C8)</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['distalfingerflexors']) && $row['distalfingerflexors'] != "") ? $row['distalfingerflexors']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Quadriceps knee jerk(L4)</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['quadricepskneejerk']) && $row['quadricepskneejerk'] != "") ? $row['quadricepskneejerk']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Ankle jerk(S1)</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['anklejerk']) && $row['anklejerk'] != "") ? $row['anklejerk']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Jaw Jerk (5th cranial nerve)</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['jawjerk']) && $row['jawjerk'] != "") ? $row['jawjerk']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-12">
                            <h4 class="h4 text-secondary mt-4 mb-3">Special Test</h4>
                        </div>

                        
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Special Test</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['specialtest']) && $row['specialtest'] != "") ? $row['specialtest']  : "N/A"; ?></p>
                        </div>


                        <div class="col-12 col-sm-12 col-md-12">
                            <h4 class="h4 text-secondary mt-4 mb-3">Assessment</h4>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Assessment</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['assessment']) && $row['assessment'] != "") ? $row['assessment']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Prognosis</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['prognosis']) && $row['prognosis'] != "") ? $row['prognosis']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-12">
                            <h4 class="h4 text-secondary mt-4 mb-3">Plan</h4>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Plan</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['plan']) && $row['plan'] != "") ? $row['plan']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-12">
                            <h4 class="h4 text-secondary mt-4 mb-3">Short Term Goals</h4>
                        </div>
                        <!-- ================== -->

                        <!-- Continue the pattern for the next set of fields -->
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Short Term Goal 1:</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['shorttermgoal1']) && $row['shorttermgoal1'] != "") ? $row['shorttermgoal1']  : "N/A"; ?></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Short Term Goal 2:</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['shorttermgoal2']) && $row['shorttermgoal2'] != "") ? $row['shorttermgoal2']  : "N/A"; ?></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Short Term Goal 3:</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['shorttermgoal3']) && $row['shorttermgoal3'] != "") ? $row['shorttermgoal3']  : "N/A"; ?></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Short Term Goal 4:</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['shorttermgoal4']) && $row['shorttermgoal4'] != "") ? $row['shorttermgoal4']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-12">
                            <h4 class="h4 text-secondary mt-4 mb-3">Long Term Goals</h4>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Long Term Goal 1:</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['longtermgoal1']) && $row['longtermgoal1'] != "") ? $row['longtermgoal1']  : "N/A"; ?></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Long Term Goal 2:</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['longtermgoal2']) && $row['longtermgoal2'] != "") ? $row['longtermgoal2']  : "N/A"; ?></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Long Term Goal 3:</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['longtermgoal3']) && $row['longtermgoal3'] != "") ? $row['longtermgoal3']  : "N/A"; ?></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Long Term Goal 4:</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['longtermgoal4']) && $row['longtermgoal4'] != "") ? $row['longtermgoal4']  : "N/A"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-12">
                            <h4 class="h4 text-secondary mt-4 mb-3">Goal Status</h4>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Goal Status 1:</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['goal_status_1']) && $row['goal_status_1'] != "") ? $row['goal_status_1']  : "N/A"; ?></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Goal Status 2:</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['goal_status_2']) && $row['goal_status_2'] != "") ? $row['goal_status_2']  : "N/A"; ?></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Goal Status 3:</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['goal_status_3']) && $row['goal_status_3'] != "") ? $row['goal_status_3']  : "N/A"; ?></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Goal Status 4:</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['goal_status_4']) && $row['goal_status_4'] != "") ? $row['goal_status_4']  : "N/A"; ?></p>
                        </div>
                        <!-- Add other echoed data accordingly -->

                    </div>
                </div>
            </div>

        <?php
        } else {
            echo "<tr><td colspan='50'>No records found</td></tr>";
        }
        ?>

    </div>

    <script src="js/jquery.min.js"></script>
    <script src="js/printThis.js"></script>
    <script src="js/html2pdf.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.btn-print').on("click", function() {
                var PrintThis = $('.container-print').clone();
                PrintThis.printThis({
                    loadCSS: "/css/specification-print.css"
                });
            });
            $('#downloadBtn').on('click', function() {
                const element = document.getElementById('pdfSection');

                // Use html2pdf library to generate PDF
                html2pdf(element);
            });
        })
    </script>

</body>

</html>