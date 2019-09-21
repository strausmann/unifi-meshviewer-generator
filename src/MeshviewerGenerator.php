<?php

namespace ISPServerfarm\UnifiMeshviewer;

use UniFi_API\Client as UnifiClient;

class MeshviewerGenerator{

    protected $unifi_url    = "";
    protected $unifi_user   = "";
    protected $unifi_pass   = "";
    protected $unifi_site   = "";
    protected $client = null;
    protected $client_debug = null;
    protected $client_loginresult = null;
    protected $alldevices = null;
    protected $allaccesspoints = [];

    private $nodelist = [];
    private $nodes_array = [];
    private $meshview = null;
    private $model = null;
    private $link = [];
    private $links = [];
    private $radio_stats = null;
    private $radio_stat0 = null;
    private $radio_stat1 = null;
    private $ap_metadata = null;
    private $firmware_base = 'Ubiquiti Networks';
    private $writeStatus = [];
    private $gateway_next_hop = null;
    private $gateway = null;

    public function __construct()
    {
        $this->client = new UnifiClient(
            getenv('UNIFI_USER'),
            getenv('UNIFI_PASS'),
            getenv('UNIFI_URL'),
            getenv('UNIFI_ZONE'),
            getenv('UNIFI_VERSION'));
    }

    public function outputDebug(){
        echo $this->unifi_pass;
        echo "debug";
    }

    public function enableDebug(){
        $this->client_debug_mode = $this->client->set_debug(true);
    }

    private function addLink($device){
        $this->link[$device->serial]["type"] = "other";
        $this->link[$device->serial]["source"] = strtolower($device->serial);
        $this->link[$device->serial]["target"] = $this->getGatewayId();
        if ($device->state == 1){
            $this->link[$device->serial]["source_tq"] = 1;
            $this->link[$device->serial]["target_tq"] = 1;
        } else {
            $this->link[$device->serial]["source_tq"] = 0;
            $this->link[$device->serial]["target_tq"] = 0;
        }
        $this->link[$device->serial]["source_addr"] = $device->mac;
        $this->link[$device->serial]["target_addr"] = $this->getGatewayMac();
    }

    private function getLinks(){
        $return = [];
        foreach ($this->link as $link) {
            $return[] = $link;
        }
        return $return;
    }

    private function login(){
        if (is_null($this->client_loginresult)){
            $this->client_loginresult = $this->client->login();
        }
    }

    private function getDevices(){
        $this->login();
        return $this->alldevices = $this->client->list_devices();
    }

    private function getAccessPoints(){
        $this->getDevices();
        foreach ($this->alldevices as $device) {
            if ($device->type === 'uap'){
                $this->writeDeviceCache($device);
                $this->writeDeviceFile($device);
                $this->allaccesspoints[$device->serial] = $device;
            }
        }
        return $this->allaccesspoints;
    }

    private function getAllAccessPoints(){
        if (empty($this->allaccesspoints)){
            $this->getAccessPoints();
        }
        return $this->allaccesspoints;
    }

    private function getAccessPointBySerial($serial){
        $tmp = $this->getAllAccessPoints();
        return $tmp[$serial];
    }

    private function getModel($model){
        switch ($model) {
            case "U7MSH":
                return "UAP-AC-Mesh";
                break;
            case "U7MP":
                return "UAP-AC-M-Pro";
                break;
            case "U7PG2":
                return "UAP-AC-Pro Gen2";
                break;
            case "U7LT":
                return "UAP-AC-Lite";
                break;
            case "U7LR":
                return "UAP-AC-LR";
                break;
            case "U7P":
                return "UAP-Pro";
                break;
            default:
                return $model;
        }
    }

    private function getPosition($device){
        if ((isset($device->x) and isset($device->y)) and (!empty($device->x) and !empty($device->y))){
            $return = [];
            $return['lat']  = $device->x;
            $return['long'] = $device->y;
            return $return;
        } else {
            return false;
        }
    }

    private function buildNodesForNodelist(){
        $devices = $this->getAllAccessPoints();
        $return = [];
        foreach ($devices as $device) {
            $ap_metadata = $this->loadDeviceByDeviceID($device->serial);
            $position = $this->getPosition($device);
            if ($ap_metadata){
                $node = [];
                $node['id'] = $device->serial;
                $node['name'] = $ap_metadata['name'];
                if ($position){
                    $node['position']['longitude']  = $position['long'];
                    $node['position']['latitude']   = $position['lat'];
                }
                if ($device->state == 1){
                    $node['status']['online'] = true;
                    $node['status']['lastcontact'] = date(DATE_ISO8601,$device->last_seen);
                } else {
                    $node['status']['online'] = false;
                    $node['status']['lastcontact'] = $ap_metadata['last_seen'];
                }
                $node['status']['clients'] = $device->num_sta;
                $return[] = $node;
                unset($node);
            }
        }
        return $return;
    }

    private function buildNodesForMeshviewerList(){
        $devices = $this->getAllAccessPoints();
        $return = [];
        foreach ($devices as $device) {
            $this->addLink($device);
            $ap_metadata = $this->loadDeviceByDeviceID($device->serial);
            $position = $this->getPosition($device);
            if (isset($ap_metadata['name'])){
                $name = $ap_metadata['name'];
            } elseif (isset($device->name)) {
                $name = $device->name;
            } else {
                $name = "Unnamed";
            }
            $node = [];
            if ($device->state == 1 and $this->isGatewayOnline()){
                $stats = $device->stat;
                $radio_stats = (array) $device->radio_table_stats;
                $radio_stat0 = $radio_stats[0];
                $radio_stat1 = $radio_stats[1];
                $node['firstseen'] = $ap_metadata['first_seen'];
                $node['lastseen'] = date(DATE_ISO8601, $device->last_seen);
                $node['is_online'] = true;
                $node['is_gateway'] = false;
                $node['clients'] = $device->num_sta;
                $node['clients_wifi24'] = $radio_stat0->num_sta;
                $node['clients_wifi5'] = $radio_stat1->num_sta;
                $node['clients_other'] = 0;

                $stats = $device->sys_stats;
                $avg = $stats->loadavg_1;
                $node['loadavg'] = floatval($avg);
                $node['memory_usage'] = $stats->mem_used/$stats->mem_total;
            } else {
                $node['firstseen'] = $ap_metadata['first_seen'];
                $node['lastseen'] = $ap_metadata['last_seen'];
                $node['is_online'] = false;
                $node['is_gateway'] = false;
                $node['clients'] = 0;
                $node['clients_wifi24'] = 0;
                $node['clients_wifi5'] = 0;
                $node['clients_other'] = 0;
                $node['loadavg'] = 0;
                $node['memory_usage'] = 0;
            }
            $node['uptime']             = $ap_metadata['uptime'];
            $node['gateway_nexthop']    = getenv('GATEWAY_NEXTHOP');
            $node['gateway']            = $this->getGatewayId();
            $node['node_id']            = strtolower($device->serial);
            $node['mac']                = $device->mac;
            $node['addresses']          = [$device->ip];
            $node['site_code']          = $this->gateway['domain'];
            $node['hostname']           = $name;
            $node['owner']              = $ap_metadata['owner'];
            if ($position){
                $node['location']['longitude']  = $position['long'];
                $node['location']['latitude']   = $position['lat'];
            }
            $node['firmware']['base']           = $this->firmware_base;
            $node['firmware']['release']        = $device->version;
            $node['autoupdater']['enabled']     = false;
            $node['autoupdater']['release']     = 'stable';
            $node['nproc'] = 1;
            $node['model'] = $this->getModel($device->model);
            $node['vpn'] = false;
            $return[] = $node;
            unset($node, $name, $ap_metadata);
        }
        return $return;
    }

    private function getMeshViewerDefaultData(){
        try{
            return json_decode(@file_get_contents(getenv('FREIFUNK_MESHVIEWERURL')), true);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    private function loadGateway($GatewayId){
        $response = $this->getMeshViewerDefaultData();
        if (isset($response['nodes'])){
            foreach ($response['nodes'] as $node) {
                if($node['node_id'] == $GatewayId){
                    unset($response);
                    $this->writeGatewayCache($node);
                    return $this->gateway = $node;
                }
            }
            return $this->gateway = false;
        }
    }

    private function getGatewayMac(){
        if (is_null($this->gateway)){
            $this->loadGateway(getenv('GATEWAY_NEXTHOP'));
        }
        return $this->gateway['mac'];
    }

    private function getGatewayId(){
        if (is_null($this->gateway)){
            $this->loadGateway(getenv('GATEWAY_NEXTHOP'));
        }
        return $this->gateway['gateway_nexthop'];
    }

    public function isGatewayOnline(){
        if (is_null($this->gateway)){
            $this->loadGateway(getenv('GATEWAY_NEXTHOP'));
        }
        if ($this->gateway){
            return $this->gateway['is_online'];
        } else {
            return false;
        }
    }

    private function buildNodelist(){
        $this->nodelist['version'] = '1.0.1';
        $this->nodelist['updated_at'] = date(DATE_ISO8601);
        $this->nodelist['nodes'] = $this->buildNodesForNodelist();
        return $this->nodelist;
    }

    private function buildMeshviewerList(){
        $this->meshview['timestamp'] = date(DATE_ISO8601);
        $this->meshview['nodes'] = $this->buildNodesForMeshviewerList();
        $this->meshview['links'] = $this->getLinks();
        return $this->meshview;
    }

    private function outputNodelist(){
        return json_encode($this->buildNodelist(),JSON_PRETTY_PRINT);
    }

    private function outputMeshviewerList(){
        return json_encode($this->buildMeshviewerList(),JSON_PRETTY_PRINT);
    }

    public function writeNodeListFile(){
        $return_nodeList = $this->outputNodelist();
        $response = file_put_contents('data/nodelist.json', $return_nodeList);
        if ($response){
            $this->writeStatus['nodelist'] = true;
        } else {
            $this->writeStatus['nodelist'] = false;
        }
    }

    public function writeMeshviewerListFile(){
        $return_nodeList = $this->outputMeshviewerList();
        $response = file_put_contents('data/meshviewer.json', $return_nodeList);
        if ($response){
            $this->writeStatus['meshviewer'] = true;
        } else {
            $this->writeStatus['meshviewer'] = false;
        }
    }

    public function writeDeviceCache($device){
        if ($device->state == 1){
            file_put_contents("../cache/".$device->serial.".json", json_encode($device,JSON_PRETTY_PRINT));
        }
    }

    public function writeGatewayCache($gateway){
        if ($gateway['is_online'] == 1){
            file_put_contents("../cache/".$gateway['node_id'].".json", json_encode($gateway,JSON_PRETTY_PRINT));
        }
    }

    public function writeDeviceFile($device){
        if ($this->checkDeviceFileExists($device->serial)){
            if ($device->state == 1){
                $deviceData = $this->loadDeviceByDeviceId($device->serial);
                $deviceData['name_internal'] = isset($device->name) ? $device->name : "Unkown";
                $deviceData['ip'] = $device->ip;
                $deviceData['last_seen'] = date(DATE_ISO8601,$device->last_seen);
                $deviceData['uptime'] = date(DATE_ISO8601,time()-$device->uptime);
                $deviceData['owner'] = getenv('OWNER_EMAIL');
            }
        } else {
            $deviceData = [];
            $deviceData['name'] = "";
            $deviceData['name_internal'] = isset($device->name) ? $device->name : "Unkown";
            $deviceData['nodeid'] = $device->serial;
            $deviceData['mac'] = $device->mac;
            $deviceData['ip'] = $device->ip;
            $deviceData['first_seen'] = date(DATE_ISO8601);
            $deviceData['last_seen'] = isset($device->last_seen) ? date(DATE_ISO8601,$device->last_seen) : date(DATE_ISO8601,time(2019-01-01));
            $deviceData['uptime'] = isset($device->uptime) ? date(DATE_ISO8601,time()-$device->uptime) : date(DATE_ISO8601,time()-1);
            $deviceData['owner'] = getenv('OWNER_EMAIL');
        }
        isset($deviceData) ? $this->saveDeviceFile($device->serial, $deviceData) : "";

    }

    private function saveDeviceFile($deviceId, array $deviceData = []){
        if (!empty($deviceData)){
            file_put_contents("../devices/".$deviceId.".json", json_encode($deviceData,JSON_PRETTY_PRINT));
        }
    }

    public function loadDeviceCacheByDeviceID($deviceId){
        if(file_exists("../cache/".$deviceId.".json")){
            return json_decode(file_get_contents("../cache/".$deviceId.".json"), true);
        } else {
            return false;
        }
    }

    public function loadGatewayCacheByGatewayID($gatewayId){
        if(file_exists("../cache/".$gatewayId.".json")){
            return json_decode(file_get_contents("../cache/".$gatewayId.".json"), true);
        } else {
            return false;
        }
    }

    public function loadDeviceByDeviceId($deviceId){
        if(file_exists("../devices/".$deviceId.".json")){
            return json_decode(file_get_contents("../devices/".$deviceId.".json"),true);
        }
    }

    public function checkDeviceFileExists($deviceId){
        if(file_exists("../devices/".$deviceId.".json")){
            return true;
        } else {
            return false;
        }
    }

    public function checkDeviceCacheFileExists($deviceId){
        if(file_exists("../cache/".$deviceId.".json")){
            return true;
        } else {
            return false;
        }
    }

    private function returnWriteStatus(){
        $return = [];
        if (isset($this->writeStatus['nodelist']) and isset($this->writeStatus['meshviewer'])){
            if ($this->writeStatus['nodelist'] === true and $this->writeStatus['meshviewer'] === true) {
                $return['status'] = true;

            } else {
                $return['status'] = false;

            }
            $return['json']['nodelist'] = $this->writeStatus['nodelist'];
            $return['json']['meshviewer'] = $this->writeStatus['meshviewer'];
        } else {
            $return['status'] = false;
            if (!isset($this->writeStatus['nodelist'])){
                $return['json']['nodelist'] = "status unknown";
            } else {
                $return['json']['nodelist'] = $this->writeStatus['nodelist'];
            }
            if (!isset($this->writeStatus['meshviewer'])){
                $return['json']['meshviewer'] = "status unknown";
            } else {
                $return['json']['meshviewer'] = $this->writeStatus['meshviewer'];
            }
        }
        $return['package_version'] = \PackageVersions\Versions::getVersion('ispserverfarm/unifi-meshviewer-generator');
        return $return;
    }

    public function executeTask(){
        $this->writeNodeListFile();
        $this->writeMeshviewerListFile();
        return json_encode($this->returnWriteStatus(),JSON_PRETTY_PRINT);
    }
}