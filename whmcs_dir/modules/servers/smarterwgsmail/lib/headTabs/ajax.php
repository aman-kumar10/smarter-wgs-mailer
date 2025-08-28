<?php

use WHMCS\ClientArea;
use WHMCS\Service\Service;
use WHMCS\Module\Server\SmarterWgsMail\Helper;

require_once dirname(__DIR__, 5) . '/init.php';
require_once dirname(__DIR__, 5) . '/includes/modulefunctions.php';

// Only allow logged-in clients
$ca = new ClientArea();
$ca->requireLogin();

$helper = new Helper();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $whmcs->get_req_var('action') === 'headTabRequest') {
    $serviceId = (int) $whmcs->get_req_var('serviceid');
    $tab = preg_replace('/[^a-zA-Z0-9_-]/', '', $whmcs->get_req_var('tab'));

    if (!$serviceId) {
        http_response_code(400);
        echo "Service ID missing";
        exit;
    }

    // Load service
    $service = Service::find($serviceId);
    if (!$service) {
        http_response_code(404);
        echo "Service not found";
        exit;
    }

    $params = ModuleBuildParams($serviceId);

    $helper = new Helper($params);

    // For hostingInfo
    if($tab == 'hostingInfo') {
        $domainData = $helper->sysadmin_getDomainData();


        if (is_array($domainData)) {
            if (!empty($domainData)) {
                $formattedData = [];
                foreach ($domainData as $label => $value) {
                    if (is_array($value) || is_object($value) || (is_null($value) || trim((string) $value) === '')) {
                        continue;
                    }
                    $formattedData[$helper->labelFormat($label)] = $value;
                }

                $html = '';
                foreach ($formattedData as $label => $value) {
                    if (is_null($value) || trim((string) $value) === '') {
                        continue;
                    }

                    if ($value === true) {
                        $displayValue = '<i class="fa fa-check" style="color: #02af02" aria-hidden="true"></i>';
                    } elseif ($value === false) {
                        $displayValue = '<i class="fa fa-times" style="color: red" aria-hidden="true"></i>';
                    } else {
                        $displayValue = htmlspecialchars((string) $value);
                    }
                    $html .= '
                        <div class="row mb-2">
                            <div class="col-sm-5 text-left">
                                <strong>' . htmlspecialchars($label) . '</strong>
                            </div>
                            <div class="col-sm-7 text-left">
                                ' . $displayValue . '
                            </div>
                        </div>
                    ';
                }
            } else {
                $html = '<div class="alert alert-warning">No data found</div>';
            }
        } elseif (is_string($domainData) && !empty($domainData)) {
            $html = '<div class="alert alert-info">' . htmlspecialchars($domainData) . '</div>';
        } else {
            $html = '<div class="alert alert-warning">No data found</div>';
        }

    }

    // For licenseInfo
    if($tab == 'licenseInfo') {
        $domainLicense = $helper->sysadmin_getDomainLicense();

        if (is_array($domainLicense)) {
            if (!empty($domainLicense)) {
                $formattedData = [];
                foreach ($domainLicense as $label => $value) {
                    if (is_array($value) || is_object($value) || (is_null($value) || trim((string) $value) === '')) {
                        continue;
                    }
                    $formattedData[$helper->labelFormat($label)] = $value;
                }

                $html = '';
                foreach ($formattedData as $label => $value) {
                    if (is_null($value) || trim((string) $value) === '') {
                        continue;
                    }

                    if ($value === true) {
                        $displayValue = '<i class="fa fa-check" style="color: #02af02" aria-hidden="true"></i>';
                    } elseif ($value === false) {
                        $displayValue = '<i class="fa fa-times" style="color: red" aria-hidden="true"></i>';
                    } else {
                        $displayValue = htmlspecialchars((string) $value);
                    }
                    $html .= '
                        <div class="row mb-2">
                            <div class="col-sm-5 text-left">
                                <strong>' . htmlspecialchars($label) . '</strong>
                            </div>
                            <div class="col-sm-7 text-left">
                                ' . $displayValue . '
                            </div>
                        </div>
                    ';
                }
            } else {
                $html = '<div class="alert alert-warning">No data found</div>';
            }
        } elseif (is_string($domainLicense) && !empty($domainLicense)) {
            $html = '<div class="alert alert-info">' . htmlspecialchars($domainLicense) . '</div>';
        } else {
            $html = '<div class="alert alert-warning">No data found</div>';
        }


    }

    // For Settings
    if($tab == 'domainSettings') {
        $domainSettings = $helper->sysadmin_getDomainSettings();

        if (is_array($domainSettings)) {
            if (!empty($domainSettings)) {
                $formattedData = [];

                foreach ($domainSettings as $label => $value) {
                    if (is_array($value) || is_object($value) || (is_null($value) || trim((string) $value) === '')) {
                        continue;
                    }

                    $formattedData[$helper->labelFormat($label)] = $value;
                }

                if (!empty($formattedData)) {
                    $html = '';
                    foreach ($formattedData as $label => $value) {
                        if (is_null($value) || trim((string) $value) === '') {
                            continue;
                        }
                        if ($value === true) {
                            $displayValue = '<i class="fa fa-check" style="color: #02af02" aria-hidden="true"></i>';
                        } elseif ($value === false) {
                            $displayValue = '<i class="fa fa-times" style="color: red" aria-hidden="true"></i>';
                        } elseif (is_numeric($value) && $label && stripos($label, 'size') !== false) {
                            $displayValue = $helper->formatSize((float) $value);
                        } else {
                            $displayValue = htmlspecialchars((string) $value);
                        }
                        
                        $html .= '
                            <div class="row mb-2">
                                <div class="col-sm-5 text-left">
                                    <strong>' . htmlspecialchars($label) . '</strong>
                                </div>
                                <div class="col-sm-7 text-left">
                                    ' . $displayValue . '
                                </div>
                            </div>
                        ';
                    }
                } else {
                    $html = '<div class="alert alert-warning">No displayable data found</div>';
                }

            } else {
                $html = '<div class="alert alert-warning">No data found</div>';
            }
        } elseif (is_string($domainSettings) && !empty($domainSettings)) {
            $html = '<div class="alert alert-info">' . htmlspecialchars($domainSettings) . '</div>';
        } else {
            $html = '<div class="alert alert-warning">No data found</div>';
        }

    }

    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
    exit;
}

http_response_code(400);
echo "Invalid request";
exit;