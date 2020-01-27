<?php

require('includes/application_top.php');
require('NewsmanClient.php');

CONST CONFIG_USER_ID = "userId";
CONST CONFIG_API_KEY = "apiKey";

$data = array(
    "userId" => "",
    "apiKey" => "",
    "lists" => array(),
    "listS" => "",
    "client" => null,
    "msg" => "",
    "btns" => "enabled"
);

$apiForm = $db->Execute("SELECT `configuration_value` FROM " . TABLE_CONFIGURATION . " WHERE `configuration_key` = 'apiForm'");
foreach ($apiForm as $_apiForm) {
    $apiForm = $_apiForm["configuration_value"];
    $apiForm = json_decode($apiForm);
    $data["userId"] = $apiForm->userId;
    $data["apiKey"] = $apiForm->apiKey;
    $data["listS"] = $apiForm->listS;

    foreach ($apiForm->lists as $list) {
        $data["lists"][] = array(
            "id" => $list->id,
            "name" => $list->name
        );
    }
}

if (!empty($data["userId"]) && !empty($data["apiKey"])) {
    $data["client"] = new Newsman_Client($data["userId"], $data["apiKey"]);
}

if (isset($_POST["apiForm"])) {

    $apiForm = array(
        "apiKey" => $_POST["apiKey"],
        "userId" => $_POST["userId"]
    );
    $data["userId"] = $_POST["userId"];
    $data["apiKey"] = $_POST["apiKey"];

    $data["client"] = new Newsman_Client($data["userId"], $data["apiKey"]);
    try {
        $lists = $data["client"]->list->all();

        foreach ($lists as $list) {
            $data["lists"][] = array(
                "id" => $list["list_id"],
                "name" => $list["list_name"]
            );
        }

        $apiForm["lists"] = $data["lists"];

        $data["msg"] = "Credentials are OK, you are connected";
    } catch (Exception $ex) {
        $data["msg"] = "Credentials are wrong, set a correct User Id & Api Key";
    }

    $db->Execute("DELETE FROM " . TABLE_CONFIGURATION . " WHERE `configuration_key` = 'apiForm'");
    $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (`configuration_title`, `configuration_key`, `configuration_value`, `configuration_description`, `configuration_group_id`, `sort_order`, `last_modified`, `date_added`, `use_function`, `set_function`, `val_function`) VALUES ('Newsman', '" . "apiForm" . "', '" . json_encode($apiForm) . "', 'newsman', '1', '1', '" . date('Y-m-d H:i:s') . "','" . date('Y-m-d H:i:s') . "', '', '', '')");

} elseif (isset($_POST["listForm"])) {

    $data["listS"] = $_POST["lists"];

    $db->Execute("DELETE FROM " . TABLE_CONFIGURATION . " WHERE `configuration_key` = 'apiForm'");
    $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (`configuration_title`, `configuration_key`, `configuration_value`, `configuration_description`, `configuration_group_id`, `sort_order`, `last_modified`, `date_added`, `use_function`, `set_function`, `val_function`) VALUES ('Newsman', '" . "apiForm" . "', '" . json_encode($data) . "', 'newsman', '1', '1', '" . date('Y-m-d H:i:s') . "','" . date('Y-m-d H:i:s') . "', '', '', '')");

} elseif (isset($_POST["syncForm"])) {

    $customers = $db->Execute("SELECT customers_email_address, customers_firstname, customers_lastname
                                    FROM " . TABLE_CUSTOMERS . "
                                    WHERE customers_newsletter = " . 1);


    //Customers
    $batchSize = 5000;

    $customers_to_import = array();

    foreach ($customers as $item) {
        $customers_to_import[] = array(
            "email" => $item["customers_email_address"],
            "firstname" => $item["customers_firstname"],
            "lastname" => $item["customers_lastname"]
        );

        if ((count($customers_to_import) % $batchSize) == 0) {
            _importData($customers_to_import, $data["listS"], null, $data["client"]);
        }
    }

    if (count($customers_to_import) > 0) {
        _importData($customers_to_import, $data["listS"], null, $data["client"]);
    }

    unset($customers_to_import);

    $data["msg"] .= PHP_EOL . "Customers with newsletter active imported";

}

function safeForCsv($str)
{
    return '"' . str_replace('"', '""', $str) . '"';
}

function _importData(&$data, $list, $segments = null, $client)
{
    $csv = '"email","firstname","lastname","source"' . PHP_EOL;

    $source = safeForCsv("ZenCart 1.5 newsman plugin");
    foreach ($data as $_dat) {
        $csv .= sprintf(
            "%s,%s,%s,%s",
            safeForCsv($_dat["email"]),
            safeForCsv($_dat["firstname"]),
            safeForCsv($_dat["lastname"]),
            $source
        );
        $csv .= PHP_EOL;
    }

    $ret = null;
    try {
        $ret = $client->import->csv($list, $segments, $csv);

        if ($ret == "") {
            throw new Exception("Import failed");
        }
    } catch (Exception $e) {

    }

    $data = array();
}

?>

<!doctype html>
<head>
    <title><?php echo TITLE; ?></title>
    <link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
    <link rel="stylesheet" type="text/css" href="includes/cssjsmenuhover.css" media="all" id="hoverJS">
    <script src="includes/menu.js"></script>
    <script src="includes/general.js"></script>
</head>
<body>
<div class="row">
    <div class="col-sm-9" style="padding-top: 30px;">
        <p>Newsman plugin for ZenCart 1.5.x.</p>
    </div>

    <div class="col-sm-9" style="padding-top: 30px;">
        <h2>
            <?php
            if (!empty($data["msg"])) {
                echo $data["msg"];
            }
            ?>
        </h2>
    </div>

    <div class="col-xs-12 formArea" style="padding-top: 30px;">

        <div class="form-group">

            <label class="col-sm-3 control-label">Data</label>
            <div class="col-sm-9 col-md-6">
                <form method="post" action="newsman.php" enctype="application/x-www-form-urlencoded">
                    <div>
                        <input class="form-control" type="text" name="userId" placeholder="user id"
                               value="<?php echo $data["userId"]; ?>" required/>
                    </div>
                    <div>
                        <input class="form-control" type="text" name="apiKey" placeholder="api key"
                               value="<?php echo $data["apiKey"]; ?>" required/>
                    </div>
                    <div>
                        <p>Generate you api key from Newsman <a href="https://www.newsman.app/" target="_blank">here</a>
                        </p>
                    </div>
                    <div>
                        <input class="btn btn-primary" type="submit" name="apiForm" value="Save"/>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xs-12 formArea" style="padding-top: 30px;">

        <div class="form-group">

            <label class="col-sm-3 control-label">Lists</label>
            <div class="col-sm-9 col-md-6">
                <form method="post" action="newsman.php" enctype="application/x-www-form-urlencoded">
                    <div>
                        <select class="form-control" name="lists">
                            <?php
                            $selected = "";
                            foreach ($data["lists"] as $list) {
                                if ($list["id"] == $data["listS"]) {
                                    $selected = "selected";
                                } else {
                                    $selected = "";
                                }
                                ?>
                                <option <?php echo $selected; ?>
                                        value="<?php echo $list['id']; ?>"><?php echo $list["name"]; ?></option>
                                <?php
                            }
                            ?>
                    </div>
                    <div>
                        <input class="btn btn-primary" type="submit" name="listForm" value="Save"/>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xs-12 formArea" style="padding-top: 30px;">

        <div class="form-group">

            <label class="col-sm-3 control-label">Sync</label>
            <div class="col-sm-9 col-md-6">
                <form method="post" action="newsman.php" enctype="application/x-www-form-urlencoded">
                    <div>
                        <input class="btn btn-primary" type="submit" name="syncForm" value="Sync Now"/>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
</body>
</html>