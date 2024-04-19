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

if ((empty($_GET['token_main'])) && (empty($_GET['term']))) {
    // Below functions are from auth.inc, which is included in globals.php
    authCloseSession();
    authLoginScreen(false);
}

$pid = $_GET['term'];

$db = new sqlDb($conn);

$tableName= 'missed_visit_notes';

$msg = '';

if (isset($_POST['submit']) && $_POST['submit'] == "intake") {

    $form_data = $_POST;
    $data=$_POST;

    $data['patient_id'] = $pid;

    $N = $data;

    print_r($data);

    if( $db->perform($tableName, $N) )
    {
      echo 'Record Inserted successfully!';
      header("Location:index.php?token_main=" . $_GET['token_main'] . '&set_pid=' . $_GET['term']); exit();
      
    }else{
        echo "Error: " . $db->error ;
    }

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
            <h1 id="title" class="text-center">Missed Visit Note</h1>
            <p id="description" class="text-center description">Motion Sync EMR</p>
            <a class="btn btn-primary" href="index.php?token_main=<?php echo $_GET['token_main'] . '&set_pid=' . $_GET['term']; ?>">Go Back</a>
        </header>
        <form action="" id="myFormr" method="post" accept-charset="utf-8">
         

            <!-- Personal Information Section -->

            <div class="mb-3">
                <label for="date_of_service" class="form-label">Date of Service</label>
                <input type="date" name="date_of_service" id="date_of_service" value="<?php echo (isset($form_data['date_of_service']) && $form_data['date_of_service'] != "" ?  $form_data['date_of_service']  : "") ?>" class="form-control"/>
            </div>

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
                <label for="cancelled" class="form-label">Cancelled</label>

                <label for="cancelledYes">
                    <input type="radio" name="cancelled" <?php echo (isset($form_data['cancelled']) && $form_data['cancelled'] == "1" ?  "checked" : "") ?> id="cancelledYes" value="1"> &nbsp; Yes
                </label>

                <label for="cancelledNo">
                    <input type="radio" name="cancelled" <?php echo (isset($form_data['cancelled']) && $form_data['cancelled'] == "0" ?  "checked" : "") ?> id="cancelledNo" value="0"> &nbsp; No

                </label>
            </div>

            <div class="mb-3">
                <label for="scheduled" class="form-label">Scheduled</label>

                <label for="scheduledYes">
                    <input type="radio" name="scheduled" <?php echo (isset($form_data['scheduled']) && $form_data['scheduled'] == "1" ?  "checked" : "") ?> id="scheduledYes" value="1"> &nbsp; Yes
                </label>

                <label for="scheduledNo">
                    <input type="radio" name="scheduled" <?php echo (isset($form_data['scheduled']) && $form_data['scheduled'] == "0" ?  "checked" : "") ?> id="scheduledNo" value="0"> &nbsp; No

                </label>
            </div>

            <div class="mb-3">
                <label for="rescheduled" class="form-label">Rescheduled</label>

                <label for="rescheduledYes">
                    <input type="radio" name="rescheduled" <?php echo (isset($form_data['rescheduled']) && $form_data['rescheduled'] == "1" ?  "checked" : "") ?> id="rescheduledYes" value="1"> &nbsp; Yes
                </label>

                <label for="rescheduledNo">
                    <input type="radio" name="rescheduled" <?php echo (isset($form_data['rescheduled']) && $form_data['rescheduled'] == "0" ?  "checked" : "") ?> id="rescheduledNo" value="0"> &nbsp; No

                </label>
            </div>

            <div class="mb-3">
                <label for="rescheduled_date" class="form-label">Rescheduled Date</label>
                <input type="date" id="rescheduled_date" name="rescheduled_date" value="<?php echo (isset($form_data['rescheduled_date']) && $form_data['rescheduled_date'] != "" ? $form_data['rescheduled_date'] : "") ?>" class="form-control"/>
            </div>

        
            <!-- Submit Button -->
            <div class="mb-3">
                <button type="submit" name="submit" value="intake" id="button" class="btn btn-primary">Submit</button>
            </div>
        </form>


    </div>


</body>

</html>