<?php
/**
 * Missions Model
 *
 * PHP Version 5.5+
 *
 * @category Model
 * @package  Application
 * @author   XG Proyect Team
 * @license  http://www.xgproyect.org XG Proyect
 * @link     http://www.xgproyect.org
 * @version  3.1.0
 */

namespace application\models\libraries\missions;

/**
 * Missions Class
 *
 * @category Classes
 * @package  Application
 * @author   XG Proyect Team
 * @license  http://www.xgproyect.org XG Proyect
 * @link     http://www.xgproyect.org
 * @version  3.1.0
 */
class Missions
{
    private $db = null;
    
    /**
     * __construct()
     */
    public function __construct($db)
    {        
        // use this to make queries
        $this->db   = $db;
        
        // lock tables
        //$this->lockTables();
    }

    /**
     * __destruct
     * 
     * @return void
     */
    public function __destruct()
    {
        // unlock tables
        //$this->unlockTables();
        
        // close connection
        $this->db->closeConnection();
    }

    /**
     * Lock all the required tables
     * 
     * @return void
     */
    private function lockTables()
    {
        $this->db->query(
            "LOCK TABLE " . ACS_FLEETS . " WRITE,
            " . ALLIANCE . " AS a WRITE,
            " . REPORTS . " WRITE,
            " . MESSAGES . " WRITE,
            " . FLEETS . " WRITE,
            " . FLEETS . " AS f WRITE,
            " . FLEETS . " AS f1 WRITE,
            " . FLEETS . " AS f2 WRITE,
            " . PLANETS . " WRITE,
            " . PLANETS . " AS pc1 WRITE,
            " . PLANETS . " AS pc2 WRITE,
            " . PLANETS . " AS p WRITE,
            " . PLANETS . " AS m WRITE,
            " . PLANETS . " AS mp WRITE,
            " . PLANETS . " AS pm WRITE,
            " . PLANETS . " AS pm2 WRITE,
            " . PREMIUM . " WRITE,
            " . PREMIUM . " AS pr WRITE,
            " . PREMIUM . " AS pre WRITE,
            " . SETTINGS . " WRITE,
            " . SETTINGS . " AS se WRITE,
            " . SHIPS . " WRITE,
            " . SHIPS . " AS s WRITE,
            " . BUILDINGS . " WRITE,
            " . BUILDINGS . " AS b WRITE,
            " . DEFENSES . " WRITE,
            " . DEFENSES . " AS d WRITE,
            " . RESEARCH . " WRITE,
            " . RESEARCH . " AS r WRITE,
            " . USERS_STATISTICS . " WRITE,
            " . USERS_STATISTICS . " AS us WRITE,
            " . USERS_STATISTICS . " AS usul WRITE,
            " . USERS . " WRITE,
            " . USERS . " AS u WRITE"
        );
    }
    
    /**
     * Unlock previously locked tables
     * 
     * @return void
     */
    private function unlockTables()
    {
        $this->db->query("UNLOCK TABLES");
    }
    
    /**
     * Delete a fleet by its ID
     * 
     * @param int $fleet_id Fleet ID
     * 
     * @return void
     */
    public function deleteFleetById($fleet_id)
    {
        if ((int)$fleet_id > 0) {

            $this->db->query(
                "DELETE FROM " . FLEETS . " WHERE `fleet_id` = '" . $fleet_id . "'"
            );
        }
    }
    
    /**
     * Update fleet status by ID
     * 
     * @param int $fleet_id Fleet ID
     * 
     * @return void
     */
    public function updateFleetStatusById($fleet_id)
    {
        if ((int)$fleet_id > 0) {

            $this->db->query(
                "UPDATE " . FLEETS . " SET
                    `fleet_mess` = '1'
                WHERE `fleet_id` = '" . $fleet_id . "'"
            );
        }
    }
    
    /**
     * Update planet ships by the provided coords and with the provided data
     * 
     * @param array $data Data to update
     * 
     * @return void
     */
    public function updatePlanetsShipsByCoords($data = [])
    {
        if (is_array($data)) {

            $this->db->query(
                "UPDATE " . PLANETS . " AS p
                INNER JOIN " . SHIPS . " AS s ON s.ship_planet_id = p.`planet_id` SET
                    {$data['ships']}
                    `planet_metal` = `planet_metal` + '" . $data['resources']['metal'] . "',
                    `planet_crystal` = `planet_crystal` + '" . $data['resources']['crystal'] . "',
                    `planet_deuterium` = `planet_deuterium` + '" . $data['resources']['deuterium'] . "'
                WHERE `planet_galaxy` = '" . $data['coords']['galaxy'] . "' AND
                    `planet_system` = '" . $data['coords']['system'] . "' AND
                    `planet_planet` = '" . $data['coords']['planet'] . "' AND
                    `planet_type` = '" . $data['coords']['type'] . "'
                LIMIT 1;"
            );   
        }
    }
    
    /**
     * Update planet resources by the provided coords and with the provided data
     * 
     * @param array $data Data to update
     * 
     * @return void
     */
    public function updatePlanetResourcesByCoords($data = [])
    {
        if (is_array($data)) {

            $this->db->query(
                "UPDATE " . PLANETS . " SET
                    `planet_metal` = `planet_metal` + '" . $data['resources']['metal'] . "',
                    `planet_crystal` = `planet_crystal` + '" . $data['resources']['crystal'] . "',
                    `planet_deuterium` = `planet_deuterium` + '" . $data['resources']['deuterium'] . "'
                WHERE `planet_galaxy` = '" . $data['coords']['galaxy'] . "' AND
                    `planet_system` = '" . $data['coords']['system'] . "' AND
                    `planet_planet` = '" . $data['coords']['planet'] . "' AND
                    `planet_type` = '" . $data['coords']['type'] . "'
                LIMIT 1;"
            );
        }
    }
    
    /**
     * Get all planet data
     * 
     * @param array $data Data to update
     * 
     * @return array
     */
    public function getAllPlanetDataByCoords($data = [])
    {
        if (is_array($data)) {

            return $this->db->queryFetch(
                "SELECT *
                FROM `" . PLANETS . "` AS p
                LEFT JOIN `" . BUILDINGS . "` AS b ON b.building_planet_id = p.`planet_id`
                LEFT JOIN `" . DEFENSES . "` AS d ON d.defense_planet_id = p.`planet_id`
                LEFT JOIN `" . SHIPS . "` AS s ON s.ship_planet_id = p.`planet_id`
                WHERE `planet_galaxy` = '" . (int)$data['coords']['galaxy'] . "' AND
                    `planet_system` = '" . (int)$data['coords']['system'] . "' AND
                    `planet_planet` = '" . (int)$data['coords']['planet'] . "' AND
                    `planet_type` = '" . (int)$data['coords']['type'] . "'
                LIMIT 1;"
            );
        }
        
        return [];
    }
    
    /**
     * Get all user data by user ID
     * 
     * @param int $user_id User ID
     * 
     * @return array
     */
    public function getAllUserDataByUserId($user_id)
    {
        if ((int)$user_id > 0) {

            return $this->db->queryFetch(
                "SELECT u.*,
                    r.*,
                    pr.*
                FROM `" . USERS . "` AS u
                INNER JOIN `" . RESEARCH . "` AS r ON r.research_user_id = u.user_id
                INNER JOIN `" . PREMIUM . "` AS pr ON pr.premium_user_id = u.user_id
                WHERE u.`user_id` = '" . $user_id . "'
                LIMIT 1;"
            );
        }
        
        return [];
    }
    
    /**
     * Delete ACS fleet by ID
     * 
     * @param int $fleet_group_id Fleet group ID
     * 
     * @return void
     */
    public function deleteAcsFleetById($fleet_group_id)
    {
        if ((int)$fleet_group_id > 0) {

            $this->db->query(
                "DELETE FROM `" . ACS_FLEETS . "`
                WHERE `acs_fleet_id` = '" . $fleet_group_id . "'"
            );
        }
    }
    
    /**
     * Update ACS fleet status by ID
     * 
     * @param string $fleet_group_id Fleet group
     * 
     * @return void
     */
    public function updateAcsFleetStatusByGroupId($fleet_group_id)
    {
        if ((int)$fleet_group_id > 0) {

            $this->db->query(
                "UPDATE `" . FLEETS . "` SET
                    `fleet_mess` = '1'
                WHERE `fleet_group` = '" . $fleet_group_id . "'"
            );
        }
    }
    
    /**
     * Get all fleets by ACS fleet ID
     * 
     * @param type $fleet_group_id
     * 
     * @return array
     */
    public function getAllAcsFleetsByGroupId($fleet_group_id)
    {
        if ((int)$fleet_group_id > 0) {

            return $this->db->queryFetchAll(
                "SELECT * 
                FROM `" . FLEETS . "`
                WHERE `fleet_group` = '" . $fleet_group_id . "';"
            );
        }
        
        return null;
    }
    
    /**
     * Get all fleets by end coordinates, start time and stay time
     * 
     * @param array $data Data to get the fleets
     * 
     * @return array
     */
    public function getAllFleetsByEndCoordsAndTimes($data = [])
    {
        if (is_array($data)) {

            return $this->db->queryFetchAll(
                "SELECT * 
                FROM `" . FLEETS . "` 
                WHERE `fleet_end_galaxy` = '" . (int)$data['coords']['galaxy'] . "' AND 
                    `fleet_end_system` = '" . (int)$data['coords']['system'] . "' AND  
                    `fleet_end_planet` = '" . (int)$data['coords']['planet'] . "' AND
                    `fleet_end_type` = '" . (int)$data['coords']['type'] . "' AND
                    `fleet_start_time` < '" . $data['time'] . "' AND 
                    `fleet_end_stay` >= '" . $data['time'] . "';"
            );
        }
        
        return [];
    }
    
    /**
     * Update planet debris by coordinates
     * 
     * @param array $data Data to run the query
     * 
     * @return void
     */
    public function updatePlanetDebrisByCoords($data = [])
    {
        if (is_array($data)) {

            $this->db->query(
                "UPDATE " . PLANETS . " SET
                    `planet_invisible_start_time` = '" . $data['time'] . "',
                    `planet_debris_metal` = `planet_debris_metal` + '" . $data['debris']['metal'] . "',
                    `planet_debris_crystal` = `planet_debris_crystal` + '" . $data['debris']['crystal'] . "'
                WHERE `planet_galaxy` = '" . (int)$data['coords']['galaxy'] . "' AND
                    `planet_system` = '" . (int)$data['coords']['system'] . "' AND
                    `planet_planet` = '" . (int)$data['coords']['planet'] . "' AND
                    `planet_type` = 1
                LIMIT 1;"
            );
        }
    }
    
    /**
     * Get user technologies by the provided user ID
     * 
     * @param int $user_id User ID
     * 
     * @return array
     */
    public function getTechnologiesByUserId($user_id)
    {
        if ((int)$user_id > 0) {

            return $this->db->queryFetch(
                "SELECT u.user_name,
                    r.research_weapons_technology,
                    r.research_shielding_technology,
                    r.research_armour_technology
                FROM " . USERS . " AS u
                    INNER JOIN `" . RESEARCH . "` AS r 
                        ON r.research_user_id = u.user_id
                WHERE u.user_id = '" . $user_id . "';"
            );
        }
    }
    
    /**
     * Get moon id by coords
     * 
     * @param array $data Moon coords
     * 
     * @return array
     */
    public function getMoonIdByCoords($data = [])
    {
        if (is_array($data)) {

            return $this->db->queryFetch(
                "SELECT `planet_id`
                FROM `" . PLANETS . "`
                WHERE `planet_galaxy` = '" . $data['coords']['galaxy'] . "'
                    AND `planet_system` = '" . $data['coords']['system'] . "'
                    AND `planet_planet` = '" . $data['coords']['planet'] . "'
                    AND `planet_type` = '3';"
            );   
        }
        
        return [];
    }
    
    /**
     * Insert a new record in the reports table
     * 
     * @param array $data Report data
     * 
     * @return void
     */
    public function insertReport($data = [])
    {
        if (is_array($data)) {

            $this->db->query(
                "INSERT INTO `" . REPORTS . "` SET
                `report_owners` = '" . $data['owners'] . "',
                `report_rid` = '" . $data['rid'] . "',
                `report_content` = '" . $data['content'] . "',
                `report_time` = '" . $data['time'] . "'"
            );   
        }
    }
    
    /**
     * Update returning fleet steal resources
     * 
     * @param type $data
     * 
     * @return void
     */
    public function updateReturningFleetResources($data = [])
    {
        if (is_array($data)) {

            $this->db->query(
                "UPDATE `" . FLEETS . "` SET
                `fleet_array` = '" . $data['ships'] . "',
                `fleet_amount` = '" . $data['amount'] . "',
                `fleet_mess` = '1',
                `fleet_resource_metal` = `fleet_resource_metal` + '" . $data['stolen']['metal'] . "' ,
                `fleet_resource_crystal` = `fleet_resource_crystal` + '" . $data['stolen']['crystal'] . "' ,
                `fleet_resource_deuterium` = `fleet_resource_deuterium` + '" . $data['stolen']['deuterium'] . "'
                WHERE `fleet_id` = '" . $data['fleet_id'] . "';"
            );   
        }
    }
    
    /**
     * Delete multiple fleets by a set of provided ids
     * 
     * @param string $id_string String of IDS
     * 
     * @return void
     */
    public function deleteMultipleFleetsByIds($id_string)
    {
        $this->db->query(
            "DELETE FROM `" . FLEETS . "`
            WHERE `fleet_id` IN (" . $id_string . ")"
        );
    }
    
    /**
     * Update planet losses by Id
     * 
     * @param array $data Data to update
     * 
     * @return void
     */
    public function updatePlanetLossesById($data = [])
    {
        if (is_array($data)) {

            // Updating defenses and ships on planet
            $this->db->query(
                "UPDATE `" . PLANETS . "`, `" . SHIPS . "`, `" . DEFENSES . "`  SET
                " . $data['ships'] . "
                `planet_metal` = `planet_metal` -  " . $data['stolen']['metal'] . ",
                `planet_crystal` = `planet_crystal` -  " . $data['stolen']['crystal'] . ",
                `planet_deuterium` = `planet_deuterium` -  " . $data['stolen']['deuterium'] . "
                WHERE `planet_id` = '" . $data['planet_id'] . "' AND
                    `ship_planet_id` = '" . $data['planet_id'] . "' AND
                    `defense_planet_id` = '" . $data['planet_id'] . "'"
            );
        }
        

    }
}

/* end of missions.php */
