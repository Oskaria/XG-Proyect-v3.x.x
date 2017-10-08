<?php
/**
 * Buildings Controller
 *
 * PHP Version 5.5+
 *
 * @category Controller
 * @package  Application
 * @author   XG Proyect Team
 * @license  http://www.xgproyect.org XG Proyect
 * @link     http://www.xgproyect.org
 * @version  3.1.0
 */

namespace application\controllers\game;

use application\core\Controller;
use application\libraries\buildings\Building;
use application\libraries\DevelopmentsLib;
use application\libraries\FormatLib;
use application\libraries\FunctionsLib;
use Exception;

/**
 * Buildings Class
 *
 * @category Classes
 * @package  Application
 * @author   XG Proyect Team
 * @license  http://www.xgproyect.org XG Proyect
 * @link     http://www.xgproyect.org
 * @version  3.1.0
 */
class Buildings extends Controller
{
    const MODULE_ID = 3;
    
    /**
     *
     * @var \Buildings
     */
    private $_building = null;
    
    /**
     * List of currently available buildings
     * 
     * @var array
     */
    private $_allowed_buildings = [];

    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        // load Model
        parent::loadModel('game/buildings');
        
        // Check module access
        FunctionsLib::moduleMessage(FunctionsLib::isModuleAccesible(self::MODULE_ID));

        $this->_user    = $this->getUserData();
        $this->_planet  = $this->getPlanetData();
        
        // init a new building object with the current building queue
        $this->setUpBuildings();
        
        // time to do something
        $this->runAction();
        
        // build the page
        $this->buildPage();
    }

    /**
     * Creates a new building object that will handle all the building
     * creation methods and actions
     * 
     * @return void
     */
    private function setUpBuildings()
    {
        $this->_building = new Building(
            $this->_planet,
            $this->_user,
            $this->getObjects()
        );
        
        $this->_allowed_buildings = $this->getAllowedBuildings();
    }    
    
    /**
     * Run an action
     * 
     * @return void
     */
    private function runAction()
    {
        $action             = filter_input(INPUT_GET, 'cmd');
        $reload             = filter_input(INPUT_GET, 'r');
        $building           = filter_input(INPUT_GET, 'building', FILTER_VALIDATE_INT);
        $list_id            = filter_input(INPUT_GET, 'listid', FILTER_VALIDATE_INT);
        $allowed_actions    = ['cancel', 'destroy', 'insert', 'remove'];

        if (!is_null($action)) {

            if (in_array($action, $allowed_actions)) {

                if ($this->canInitBuildAction($building, $list_id)) {

                    switch ($action) {
                        case 'cancel':
                            $this->cancelCurrent();
                            break;

                        case 'destroy':
                            $this->addToQueue($building, false);
                            break;

                        case 'insert':
                            $this->addToQueue($building, true);
                            break;

                        case 'remove':
                            $this->removeFromQueue($list_id);
                            break;
                    }
                }

                if ($reload == 'overview') {

                    header('location:game.php?page=overview');
                } else {

                    header('location:game.php?page=' . $this->getCurrentPage());
                }
            }
        }
        
        // set first element
        DevelopmentsLib::setFirstElement($this->_planet, $this->_user);

        // start building
        $this->Buildings_Model->updatePlanetBuildingQueue(
            $this->_planet['planet_b_building_id'],
            $this->_planet['planet_b_building'],
            $this->_planet['planet_id']
        );
    }
    
    /**
     * Build the page
     * 
     * @return void
     */
    private function buildPage()
    {        
        /**
         * Parse the items
         */
        $page                   = [];
        $page['BuildingsList']  = $this->buildListOfBuildings();
        
        // display the page
        parent::$page->display(
            parent::$page->get('buildings/buildings_builds')->parse(
                array_merge($page, $this->buildQueueBlock())
            )
        );
    }
    
    /**
     * Build the list of buildings
     * 
     * @return string
     */
    private function buildListOfBuildings()
    {
        $buildings_list = '';
        
        if (!is_null($this->_allowed_buildings)) {
            
            foreach ($this->_allowed_buildings as $building_id) {
                
                $buildings_list .= parent::$page->get('buildings/buildings_builds_row')->parse(
                    $this->setListOfBuildingsItem($building_id)
                );
            }
        }
        
        return $buildings_list;
    }
    
    /**
     * Build the list of queued elements
     * 
     * @return array
     */
    private function buildQueueBlock()
    {
        $return['BuildListScript']  = '';
        $return['BuildList']        = '';
        
        $queue = $this->showQueue();
        
        if ($queue['lenght'] > 0) {

            $return['BuildListScript']   = DevelopmentsLib::currentBuilding($this->getCurrentPage());
            $return['BuildList']         = $queue['buildlist'];
        }
        
        return $return;
    }
    
    /**
     * Build each building block
     * 
     * @param int $building_id Building ID
     * 
     * @return array
     */
    private function setListOfBuildingsItem($building_id)
    {
        $item_to_parse  = [];
        
        $item_to_parse['dpath']         = DPATH;
        $item_to_parse['i']             = $building_id;
        $item_to_parse['nivel']         = $this->getBuildingLevelWithFormat($building_id);
        $item_to_parse['n']             = $this->getLang()['tech'][$building_id];
        $item_to_parse['descriptions']  = $this->getLang()['res']['descriptions'][$building_id];
        $item_to_parse['price']         = $this->getBuildingPriceWithFormat($building_id);  
        $item_to_parse['time']          = $this->getBuildingTimeWithFormat($building_id);
        $item_to_parse['click']         = $this->getActionButton($building_id);  

        return $item_to_parse;
    }
    
    /**
     * Expects a building ID to calculate and format the level
     * 
     * @param int $building_id Building ID
     * 
     * @return string
     */
    private function getBuildingLevelWithFormat($building_id)
    {        
        return DevelopmentsLib::setLevelFormat(
            $this->getBuildingLevel($building_id)
        );
    }
    
    /**
     * Expects a building ID to calculate and format the price
     * 
     * @param int $building_id Building ID
     * 
     * @return string
     */
    private function getBuildingPriceWithFormat($building_id)
    {
        return DevelopmentsLib::formatedDevelopmentPrice(
            $this->_user,
            $this->_planet,
            $building_id,
            true,
            $this->getBuildingLevel($building_id)
        );
    }  
    
    /**
     * Expects a building ID to calculate and format the time
     * 
     * @param int $building_id Building ID
     * 
     * @return string
     */
    private function getBuildingTimeWithFormat($building_id)
    {
        return DevelopmentsLib::formatedDevelopmentTime(
            $this->getBuildingTime($building_id)
        );
    }
    
    /**
     * Expects a building ID to calculate the building level
     * 
     * @param int $building_id Building ID
     * 
     * @return int
     */
    private function getBuildingLevel($building_id)
    {        
        return $this->_planet[$this->getObjects()->getObjects()[$building_id]];
    }
    
    /**
     * Expects a building ID to calculate the building time
     * 
     * @param int $building_id Building ID
     * 
     * @return int
     */
    private function getBuildingTime($building_id)
    {
        return DevelopmentsLib::developmentTime(
            $this->_user,
            $this->_planet,
            $building_id,
            $this->getBuildingLevel($building_id)
        );
    }
    
    /**
     * Expects a building ID, runs several validations and then returns a button,
     * based on the validations
     * 
     * @param int $building_id Building ID
     * 
     * @return string
     */
    private function getActionButton($building_id)
    {
        $build_url  = 'game.php?page=' . $this->getCurrentPage() . '&cmd=insert&building=' . $building_id;
        
        // validations
        $is_development_payable = DevelopmentsLib::isDevelopmentPayable($this->_user, $this->_planet, $building_id, true, false);
        $is_on_vacations        = parent::$users->isOnVacations($this->_user);
        $have_fields            = DevelopmentsLib::areFieldsAvailable($this->_planet);
        $is_queue_full          = $this->_building->isQueueFull();
        $queue_element          = $this->_building->getCountElementsOnQueue();

        // check fields
        if (!$have_fields) {

            return $this->buildButton('all_occupied');
        }
            
        // check queue, payable and vacations
        if ($is_queue_full or !$is_development_payable or $is_on_vacations) {

            return $this->buildButton('not_allowed'); 
        }
        
        // check if there's any work in progress
        if ($this->isWorkInProgress($building_id)) {
            
            return $this->buildButton('work_in_progress'); 
        }
        
        // if a queue was already set
        if ($queue_element > 0) {

            return FunctionsLib::setUrl($build_url, '', $this->buildButton('allowed_for_queue'));
        }
        
        // any other case
        return FunctionsLib::setUrl($build_url, '', $this->buildButton('allowed'));
    }
    
    /**
     * 
     * @param int $building_id  Building ID
     * @param int $list_id      List ID
     * 
     * @return boolean
     */
    private function canInitBuildAction($building_id, $list_id)
    {
        if (isset($list_id)) {
            
            return true;
        }
        
        if (!in_array($building_id, $this->_allowed_buildings)) {

            return false;
        }

        if ($this->isWorkInProgress($building_id)) {

            return false;
        }

        return true;
    }
    
    /**
     * Get the properties for each button type
     * 
     * @param string $button_code Button code
     * 
     * @return string
     */
    private function buildButton($button_code)
    {
        $listOfButtons  = [
            'all_occupied'      => ['color' => 'red', 'lang' => 'bd_no_more_fields'],
            'allowed'           => ['color' => 'green', 'lang' => 'bd_build'],
            'not_allowed'       => ['color' => 'red', 'lang' => 'bd_build'],
            'allowed_for_queue' => ['color' => 'green', 'lang' => 'bd_add_to_list'],
            'work_in_progress'  => ['color' => 'red', 'lang' => 'bd_working']
        ];
        
        $color      = ucfirst($listOfButtons[$button_code]['color']);
        $text       = $this->getLang()[$listOfButtons[$button_code]['lang']];
        $methodName = 'color' . $color;
        
        return FormatLib::$methodName($text);
    }
    
    /**
     * Determine if there's any work in progress
     * 
     * @param int $building_id Building ID
     * 
     * @return boolean
     */
    private function isWorkInProgress($building_id)
    {
        $working_buildings  = [14, 15, 21];
        
        if ($building_id == 31 && DevelopmentsLib::isLabWorking($this->_user)) {

            return true;
        }
        
        if (in_array($building_id, $working_buildings) && DevelopmentsLib::isShipyardWorking($this->_planet)) {

            return true;
        }
        
        return false;
    }
    
    /**
     * Determine the current page and validate it
     * 
     * @return array
     * 
     * @throws Exception
     */
    private function getCurrentPage()
    {
        try {
            $get_value      = filter_input(INPUT_GET, 'page');
            $allowed_pages  = ['resources', 'station'];

            if (in_array($get_value, $allowed_pages)) {

                return $get_value;
            }
            
            throw new Exception('"resources" and "station" are the valid options');

        } catch (Exception $e) {
            
            die('Caught exception: ' . $e->getMessage() . "\n");
        }
    }
    
    /**
     * Get an array with an allowed set of items for the current page,
     * filtering by page and available technologies
     * 
     * @return array
     */
    private function getAllowedBuildings()
    {
        $allowed_buildings = [
            'resources' => [
                1 => [1, 2, 3, 4, 12, 22, 23, 24],
                3 => [12, 22, 23, 24]
            ],
            'station'   => [
                1 => [14, 15, 21, 31, 33, 34, 44],
                3 => [14, 21, 41, 42, 43]
            ]
        ];

        return array_filter($allowed_buildings[$this->getCurrentPage()][$this->_planet['planet_type']], function($value) {
            return DevelopmentsLib::isDevelopmentAllowed(
                $this->_user,
                $this->_planet,
                $value
            );
        });
    }
    
    /**
     * OLD METHODS BELOW
     * OLD METHODS BELOW
     * OLD METHODS BELOW
     * OLD METHODS BELOW
     * OLD METHODS BELOW
     */

    /**
     * method cancelCurrent
     * param
     * return (bool) confirmation
     */
    private function cancelCurrent()
    {
        $CurrentQueue   = $this->_planet['planet_b_building_id'];

        if ($CurrentQueue != 0) {

            $QueueArray         = explode(";", $CurrentQueue);
            $ActualCount        = count($QueueArray);
            $CanceledIDArray    = explode(",", $QueueArray[0]);
            $building           = $CanceledIDArray[0];
            $BuildMode          = $CanceledIDArray[4];

            if ($ActualCount > 1) {

                array_shift($QueueArray);
                $NewCount       = count($QueueArray);
                $BuildEndTime   = time();

                for ($ID = 0; $ID < $NewCount; $ID++) {
                    $ListIDArray = explode(",", $QueueArray[$ID]);

                    if ($ListIDArray[0] == $building) {
                        $ListIDArray[1] -= 1;
                    }

                    $current_build_time = DevelopmentsLib::developmentTime($this->_user, $this->_planet, $ListIDArray[0]);
                    $BuildEndTime += $current_build_time;
                    $ListIDArray[2] = $current_build_time;
                    $ListIDArray[3] = $BuildEndTime;
                    $QueueArray[$ID] = implode(",", $ListIDArray);
                }
                $NewQueue = implode(";", $QueueArray);
                $ReturnValue = true;
                $BuildEndTime = '0';
            } else {
                $NewQueue = '0';
                $ReturnValue = false;
                $BuildEndTime = '0';
            }

            if ($BuildMode == 'destroy') {
                $ForDestroy = true;
            } else {
                $ForDestroy = false;
            }

            if ($building != false) {
                $Needed = DevelopmentsLib::developmentPrice($this->_user, $this->_planet, $building, true, $ForDestroy);
                $this->_planet['planet_metal'] += $Needed['metal'];
                $this->_planet['planet_crystal'] += $Needed['crystal'];
                $this->_planet['planet_deuterium'] += $Needed['deuterium'];
            }
        } else {
            $NewQueue = '0';
            $BuildEndTime = '0';
            $ReturnValue = false;
        }

        $this->_planet['planet_b_building_id'] = $NewQueue;
        $this->_planet['planet_b_building'] = $BuildEndTime;

        return $ReturnValue;
    }

    /**
     * method removeFromQueue
     * param $QueueID
     * return (int) the queue ID
     */
    private function removeFromQueue($QueueID)
    {
        if ($QueueID > 1) {
            $CurrentQueue = $this->_planet['planet_b_building_id'];

            if (!empty($CurrentQueue)) {
                $QueueArray = explode(";", $CurrentQueue);
                $ActualCount = count($QueueArray);
                if ($ActualCount < 2) {
                    FunctionsLib::redirect('game.php?page=' . $this->getCurrentPage());
                }

                //  finding the buildings time
                $ListIDArrayToDelete = explode(",", $QueueArray[$QueueID - 1]);
                $lastB = $ListIDArrayToDelete;
                $lastID = $QueueID - 1;

                //search for biggest element
                for ($ID = $QueueID; $ID < $ActualCount; $ID++) {
                    //next buildings
                    $nextListIDArray = explode(",", $QueueArray[$ID]);
                    //if same type of element
                    if ($nextListIDArray[0] == $ListIDArrayToDelete[0]) {
                        $lastB = $nextListIDArray;
                        $lastID = $ID;
                    }
                }

                // update the rest of buildings queue
                for ($ID = $lastID; $ID < $ActualCount - 1; $ID++) {
                    $nextListIDArray = explode(",", $QueueArray[$ID + 1]);
                    $nextBuildEndTime = $nextListIDArray[3] - $lastB[2];
                    $nextListIDArray[3] = $nextBuildEndTime;
                    $QueueArray[$ID] = implode(",", $nextListIDArray);
                }

                unset($QueueArray[$ActualCount - 1]);
                $NewQueue = implode(";", $QueueArray);
            }

            $this->_planet['planet_b_building_id'] = $NewQueue;
        }

        return $QueueID;
    }

    /**
     * method addToQueue
     * param $building
     * param $AddMode
     * return (int) the queue ID
     */
    private function addToQueue($building, $AddMode = true)
    {
        $resource       = $this->getObjects()->getObjects();
        $CurrentQueue   = $this->_planet['planet_b_building_id'];
        $queue          = $this->showQueue();
        $max_fields     = DevelopmentsLib::maxFields($this->_planet);
        $QueueArray     = [];

        if ($AddMode) {
            if (($this->_planet['planet_field_current'] >= ($max_fields - $queue['lenght']))) {
                FunctionsLib::redirect('game.php?page=' . $this->getCurrentPage());
            }
        }

        if ($CurrentQueue != 0) {
            $QueueArray = explode(";", $CurrentQueue);
            $ActualCount = count($QueueArray);
        } else {
            $QueueArray = "";
            $ActualCount = 0;
        }

        if ($AddMode == true) {
            $BuildMode = 'build';
        } else {
            $BuildMode = 'destroy';
        }

        if ($ActualCount < MAX_BUILDING_QUEUE_SIZE) {
            $QueueID = $ActualCount + 1;
        } else {
            $QueueID = false;
        }

        $continue = false;

        if ($QueueID != false && DevelopmentsLib::isDevelopmentAllowed($this->_user, $this->_planet, $building)) {
            if ($QueueID <= 1) {
                if (DevelopmentsLib::isDevelopmentPayable($this->_user, $this->_planet, $building, true, false) && !parent::$users->isOnVacations($this->_user)) {
                    $continue = true;
                }
            } else {
                $continue = true;
            }

            if ($continue) {
                if ($QueueID > 1) {
                    $InArray = 0;
                    for ($QueueElement = 0; $QueueElement < $ActualCount; $QueueElement++) {
                        $QueueSubArray = explode(",", $QueueArray[$QueueElement]);
                        if ($QueueSubArray[0] == $building) {
                            $InArray++;
                        }
                    }
                } else {
                    $InArray = 0;
                }

                if ($InArray != 0) {
                    $ActualLevel = $this->_planet[$resource[$building]];
                    if ($AddMode == true) {
                        $BuildLevel = $ActualLevel + 1 + $InArray;
                        $this->_planet[$resource[$building]] += $InArray;
                        $BuildTime = DevelopmentsLib::developmentTime($this->_user, $this->_planet, $building);
                        $this->_planet[$resource[$building]] -= $InArray;
                    } else {
                        $BuildLevel = $ActualLevel - 1 - $InArray;
                        $this->_planet[$resource[$building]] -= $InArray;
                        $BuildTime = DevelopmentsLib::developmentTime($this->_user, $this->_planet, $building) / 2;
                        $this->_planet[$resource[$building]] += $InArray;
                    }
                } else {
                    $ActualLevel = $this->_planet[$resource[$building]];
                    if ($AddMode == true) {
                        $BuildLevel = $ActualLevel + 1;
                        $BuildTime = DevelopmentsLib::developmentTime($this->_user, $this->_planet, $building);
                    } else {
                        $BuildLevel = $ActualLevel - 1;
                        $BuildTime = DevelopmentsLib::developmentTime($this->_user, $this->_planet, $building) / 2;
                    }
                }

                if ($QueueID == 1) {
                    $QueueArray     = [];
                    $BuildEndTime   = time() + $BuildTime;
                } else {
                    $PrevBuild = explode(",", $QueueArray[$ActualCount - 1]);
                    $BuildEndTime = $PrevBuild[3] + $BuildTime;
                }

                $QueueArray[$ActualCount] = $building . "," . $BuildLevel . "," . $BuildTime . "," . $BuildEndTime . "," . $BuildMode;
                $NewQueue = implode(";", $QueueArray);

                $this->_planet['planet_b_building_id'] = $NewQueue;
            }
        }
        return $QueueID;
    }

    /**
     * method showQueue
     * param $Sprice
     * return (array) the queue to build data
     */
    private function showQueue(&$Sprice = false)
    {
        $lang = $this->getLang();
        $CurrentQueue = $this->_planet['planet_b_building_id'];
        $QueueID = 0;
        $to_destroy = 0;
        $BuildMode = '';

        if ($CurrentQueue != 0) {
            $QueueArray = explode(";", $CurrentQueue);
            $ActualCount = count($QueueArray);
        } else {
            $QueueArray = '0';
            $ActualCount = 0;
        }

        $ListIDRow = '';

        if ($ActualCount != 0) {
            $PlanetID = $this->_planet['planet_id'];
            for ($QueueID = 0; $QueueID < $ActualCount; $QueueID++) {
                $BuildArray = explode(",", $QueueArray[$QueueID]);
                $BuildEndTime = floor($BuildArray[3]);
                $CurrentTime = floor(time());

                if ($BuildMode == 'destroy') {
                    $to_destroy++;
                }

                if ($BuildEndTime >= $CurrentTime) {
                    $ListID = $QueueID + 1;
                    $building = $BuildArray[0];
                    $BuildLevel = $BuildArray[1];
                    $BuildMode = $BuildArray[4];
                    $BuildTime = $BuildEndTime - time();
                    $ElementTitle = $this->getLang()['tech'][$building];

                    if (isset($Sprice[$building]) && $Sprice !== false && $BuildLevel > $Sprice[$building]) {
                        $Sprice[$building] = $BuildLevel;
                    }

                    if ($ListID > 0) {
                        $ListIDRow .= "<tr>";
                        if ($BuildMode == 'build') {
                            $ListIDRow .= "	<td class=\"l\" colspan=\"2\">" . $ListID . ".: " . $ElementTitle . " " . $BuildLevel . "</td>";
                        } else {
                            $ListIDRow .= "	<td class=\"l\" colspan=\"2\">" . $ListID . ".: " . $ElementTitle . " " . $BuildLevel . " " . $this->getLang()['bd_dismantle'] . "</td>";
                        }
                        $ListIDRow .= "	<td class=\"k\">";

                        if ($ListID == 1) {
                            $ListIDRow .= "		<div id=\"blc\" class=\"z\">" . $BuildTime . "<br>";
                            $ListIDRow .= "		<a href=\"game.php?page=" . $this->getCurrentPage() . "&listid=" . $ListID . "&amp;cmd=cancel&amp;planet=" . $PlanetID . "\">" . $this->getLang()['bd_interrupt'] . "</a></div>";
                            $ListIDRow .= "		<script language=\"JavaScript\">";
                            $ListIDRow .= "			pp = \"" . $BuildTime . "\";\n";
                            $ListIDRow .= "			pk = \"" . $ListID . "\";\n";
                            $ListIDRow .= "			pm = \"cancel\";\n";
                            $ListIDRow .= "			pl = \"" . $PlanetID . "\";\n";
                            $ListIDRow .= "			t();\n";
                            $ListIDRow .= "		</script>";
                            $ListIDRow .= "		<strong color=\"lime\"><br><font color=\"lime\">" . date(FunctionsLib::readConfig('date_format_extended'), $BuildEndTime) . "</font></strong>";
                        } else {
                            $ListIDRow .= "		<font color=\"red\">";
                            $ListIDRow .= "		<a href=\"game.php?page=" . $this->getCurrentPage() . "&listid=" . $ListID . "&amp;cmd=remove&amp;planet=" . $PlanetID . "\">" . $this->getLang()['bd_cancel'] . "</a></font>";
                        }

                        $ListIDRow .= "	</td>";
                        $ListIDRow .= "</tr>";
                    }
                }
            }
        }

        $RetValue['to_destoy'] = $to_destroy;
        $RetValue['lenght'] = $ActualCount;
        $RetValue['buildlist'] = $ListIDRow;

        return $RetValue;
    }
}

/* end of buildings.php */