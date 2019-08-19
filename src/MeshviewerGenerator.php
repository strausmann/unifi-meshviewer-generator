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

    public function addLink($device){
        $this->link[$device->serial]["type"] = "other";
        $this->link[$device->serial]["source"] = strtolower($device->serial);
        $this->link[$device->serial]["target"] = getenv('GATEWAY_ID');
        $this->link[$device->serial]["source_tq"] = 1;
        $this->link[$device->serial]["target_tq"] = 1;
        $this->link[$device->serial]["source_addr"] = $device->mac;
        $this->link[$device->serial]["target_addr"] = getenv('GATEWAY_MAC');
    }

    public function getLinks(){
        $return = [];
        foreach ($this->link as $link) {
            $return[] = $link;
        }
        return $return;
    }

    public function loadAccessPointsMetaData(){
        return $this->ap_metadata = yaml_parse_file(dirname(dirname(__FILE__))."/config/accesspoints.yaml", 0);
    }

    public function loadGatewayMetaData(){
        return $this->gateway_metadata = yaml_parse_file(dirname(dirname(__FILE__))."/config/gateway.yaml", 0);
    }

    public function getAccessPointMetaDataBySerial($serial){
        if (empty($this->ap_metadata)){
            $this->loadAccessPointsMetaData();
        }
        if(isset($this->ap_metadata[$serial])){
            return $this->ap_metadata[$serial];
        } else {
            return false;
        }

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

    public function getAllAccessPoints(){
        if (empty($this->allaccesspoints)){
            $this->getAccessPoints();
        }
        return $this->allaccesspoints;
    }

    public function getAccessPointBySerial(string $serial){
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

    public function buildNodesForNodelist(){
        $devices = $this->getAllAccessPoints();
        $return = [];
        foreach ($devices as $device) {
            $ap_metadata = $this->loadDeviceByDeviceID($device->serial);
            if ($ap_metadata){
                $node = [];
                $node['id'] = $device->serial;
                $node['name'] = $ap_metadata['name'];
                if (!is_null($ap_metadata['position']['lat']) and !is_null($ap_metadata['position']['long'])){
                    $node['position']['lat'] = $ap_metadata['position']['lat'];
                    $node['position']['long'] = $ap_metadata['position']['long'];
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
        #$return[] = $this->buildGatewayNodeForNodelist();
        return $return;
    }

    public function buildNodesForMeshviewerList(){
        $devices = $this->getAllAccessPoints();
        $return = [];
        foreach ($devices as $device) {
            $this->addLink($device);
            $ap_metadata = $this->loadDeviceByDeviceID($device->serial);
            if (isset($ap_metadata['name'])){
                $name = $ap_metadata['name'];
            } elseif (isset($device->name)) {
                $name = $device->name;
            } else {
                $name = "Unnamed";
            }
            $node = [];
            if ($device->state == 1){
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
            $node['gateway_nexthop']    = getenv('GATEWAY_ID');
            $node['gateway']            = getenv('GATEWAY_NEXTHOP');
            $node['node_id']            = strtolower($device->serial);
            $node['mac']                = $device->mac;
            $node['addresses']          = [$device->ip];
            $node['site_code']          = getenv('FREIFUNK_SITEID');
            $node['hostname']           = $name;
            $node['owner']              = $ap_metadata['owner'];
            if (!is_null($ap_metadata['position']['lat']) and !is_null($ap_metadata['position']['long'])){
                $node['location']['longitude']  = $ap_metadata['position']['long'];
                $node['location']['latitude']   = $ap_metadata['position']['lat'];
            }
            $node['firmware']['base']           = 'Ubiquiti Networks';
            $node['firmware']['release']        = $device->version;
            $node['autoupdater']['enabled']     = false;
            $node['autoupdater']['release']     = 'stable';
            $node['nproc'] = 1;
            $node['model'] = $this->getModel($device->model);
            $node['vpn'] = false;
            $return[] = $node;
            unset($node, $name, $ap_metadata);
        }
        #$return[] = $this->buildGatewayNodeForMeshviewerlist();
        return $return;
    }

    public function buildGatewayNodeForNodelist(){
        $return = [];
        $return['id'] = getenv('GATEWAY_ID');
        $return['name'] = getenv('GATEWAY_NAME');
        $return['status']['online'] = true;
        $return['status']['lastcontact'] = date(DATE_ISO8601);
        $return['status']['clients'] = 0;
        return $return;
    }

    public function buildGatewayNodeForMeshviewerlist(){
        $return = [];
        #print_r(@file_get_contents('/proc/uptime'));
        #echo print_r($this->Uptime());

        $load = sys_getloadavg();

        $return['firstseen'] = date(DATE_ISO8601,time(getenv('GATEWAY_FIRSTSEEN')));
        $return['lastseen'] = date(DATE_ISO8601);
        $return['is_online'] = true;
        $return['is_gateway'] = true;
        $return['clients'] = 0;
        $return['clients_wifi24'] = 0;
        $return['clients_wifi5'] = 0;
        $return['clients_other'] = 0;
        $return['rootfs_usage'] = 0;
        $return['loadavg'] = $load[0];
        $return['memory_usage'] = 0;
    #$return['uptime'] = $uptime;
        $return['node_id'] = getenv('GATEWAY_ID');
        $return['mac'] = getenv('GATEWAY_MAC');
        $return['addresses'] = [getenv('GATEWAY_IPADDRESS')];
        $return['hostname'] = getenv('GATEWAY_NAME');
        $return['firmware']['base'] = getenv('GATEWAY_BASE');
        $return['firmware']['release'] = "RELEASE";
        $return['autoupdater']['enabled'] = false;
        $return['nproc'] = 2;
        $return['vpn'] = true;
        return $return;
    }

    public function buildMeshviewer(){
        $devices = $this->getAllAccessPoints();
        $return = [];

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

    public function outputNodelist(){
        return json_encode($this->buildNodelist());
    }

    public function outputMeshviewerList(){
        return json_encode($this->buildMeshviewerList());
    }

    public function writeNodeListFile(){
        $return_nodeList = $this->outputNodelist();
        file_put_contents('data/nodelist.json', $return_nodeList);
    }

    public function writeMeshviewerListFile(){
        $return_nodeList = $this->outputMeshviewerList();
        file_put_contents('data/meshviewer.json', $return_nodeList);
    }

    public function writeDeviceCache($device){
        if ($device->state == 1){
            file_put_contents("../cache/".$device->serial.".json", json_encode($device,JSON_PRETTY_PRINT));
        }
    }

    public function writeDeviceFile(object $device){
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
            $deviceData['position']["lat"] = null;
            $deviceData['position']["long"] = null;
            $deviceData['first_seen'] = date(DATE_ISO8601);
            $deviceData['last_seen'] = isset($device->last_seen) ? date(DATE_ISO8601,$device->last_seen) : date(DATE_ISO8601,time(2019-01-01));
            $deviceData['uptime'] = isset($device->uptime) ? date(DATE_ISO8601,time()-$device->uptime) : date(DATE_ISO8601,time()-1);
            $deviceData['owner'] = getenv('OWNER_EMAIL');
        }
        isset($deviceData) ? $this->saveDeviceFile($device->serial, $deviceData) : "";

    }

    private function saveDeviceFile(string $deviceId, array $deviceData = []){
        if (!empty($deviceData)){
            file_put_contents("../devices/".$deviceId.".json", json_encode($deviceData,JSON_PRETTY_PRINT));
        }
    }

    public function loadDeviceCacheByDeviceID(string $deviceId){
        if(file_exists("../cache/".$deviceId.".json")){
            return json_decode(file_get_contents("../cache/".$deviceId.".json"), true);
        } else {

        }
    }

    public function loadDeviceByDeviceId(string $deviceId){
        if(file_exists("../devices/".$deviceId.".json")){
            return json_decode(file_get_contents("../devices/".$deviceId.".json"),true);
        }
    }

    public function checkDeviceFileExists(string $deviceId){
        if(file_exists("../devices/".$deviceId.".json")){
            return true;
        } else {
            return false;
        }
    }

    public function checkDeviceCacheFileExists(string $deviceId){
        if(file_exists("../cache/".$deviceId.".json")){
            return true;
        } else {
            return false;
        }
    }
}