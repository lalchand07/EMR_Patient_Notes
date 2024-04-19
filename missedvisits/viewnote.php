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
    $sql = "SELECT * FROM missed_visit_notes where id = " . $_GET['term'] . "";
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
    <title>Missed Visit Note | Motion Sync EMR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/all.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="images/logo.jpg">
</head>

<body>

    <div class="container">
        <header>
            <img class="logo" src="images/logo.jpg" alt="logo">
            <h1 class="intake-title" class="text-center">Missed Visit Note</h1>
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
                    <h4 class="card-title">Missed Visit Note (<?php echo $patientData['title'] . ' ' . $patientData['fname'] . ' ' . $patientData['lname'] ?>)</h4>

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
                            <p><strong>Cancelled</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['cancelled']) && $row['cancelled'] == "1") ? 'YES'  : "NO"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Scheduled</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['scheduled']) && $row['scheduled'] == "1") ? 'YES'  : "NO"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Rescheduled</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['rescheduled']) && $row['rescheduled'] == "1") ? 'YES'  : "NO"; ?></p>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6">
                            <p><strong>Rescheduled Date</strong></p>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6">
                            <p><?php echo (isset($row['rescheduled_date']) && $row['rescheduled_date'] != "") ? $row['rescheduled_date']  : "NO"; ?></p>
                        </div>

                       
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