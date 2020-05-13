#! /usr/bin/php
<?php
require_once( __DIR__ . "/../init/init.php");

$testConfig = new OpenStackConfig();
$testConfig->openstack_authentication_url_v3  = ACTES_OPENSTACK_AUTHENTICATION_URL_V3;
$testConfig->openstack_username = ACTES_OPENSTACK_USERNAME;
$testConfig->openstack_password = ACTES_OPENSTACK_PASSWORD;
$testConfig->openstack_tenant = ACTES_OPENSTACK_TENANT;
$testConfig->openstack_region = ACTES_OPENSTACK_REGION;
$testConfig->openstack_swift_container_prefix = ACTES_OPENSTACK_SWIFT_CONTAINER_PREFIX."error";

$openStackContainerStore->addConfiguration(TestCloudStorage::CONTAINER_NAME,$testConfig);

$objectInstancier->get(WorkerScript::class);

$workerScript = $objectInstancier->get(WorkerScript::class);
$workerScript->scriptByClassName(
    TestWorker::class,
    true,
    true
);