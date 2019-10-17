<?php
/**
 * Messages entity
 *
 * PHP Version 7+
 *
 * @category Core
 * @package  Application
 * @author   XG Proyect Team
 * @license  http://www.xgproyect.org XG Proyect
 * @link     http://www.xgproyect.org
 * @version  3.1.0
 */
namespace application\core\entities;

use application\core\Entity;

/**
 * Messages entity Class
 *
 * @category Entity
 * @package  Core
 * @author   XG Proyect Team
 * @license  http://www.xgproyect.org XG Proyect
 * @link     http://www.xgproyect.org
 * @version  3.1.0
 */
class MessagesEntity extends Entity
{

    /**
     * Constructor
     * 
     * @param array $data Data
     * 
     * @return void
     */
    public function __construct($data)
    {
        parent::__construct($data);
    }
    
    /**
     * Get the message id
     * 
     * @return string
     */
    public function getMessageId()
    {
        return $this->_data['message_id'];
    }

    /**
     * Get the message sender
     * 
     * @return string
     */
    public function getMessageSender()
    {
        return $this->_data['message_sender'];
    }

    /**
     * Get the message receiver
     * 
     * @return string
     */
    public function getMessageReceiver()
    {
        return $this->_data['message_receiver'];
    }

    /**
     * Get the message time
     * 
     * @return string
     */
    public function getMessageTime()
    {
        return $this->_data['message_time'];
    }

    /**
     * Get the message type
     * 
     * @return string
     */
    public function getMessageType())
    {
        return $this->_data['message_type'];
    }

    /**
     * Get the message from
     * 
     * @return string
     */
    public function getMessageFrom()
    {
        return $this->_data['message_from'];
    }

    /**
     * Get the message subject
     * 
     * @return string
     */
    public function getMessageSubject()
    {
        return $this->_data['message_subject'];
    }

    /**
     * Get the message text
     * 
     * @return string
     */
    public function getMessageText()
    {
        return $this->_data['message_text'];
    }

    /**
     * Get the message read
     * 
     * @return string
     */
    public function getMessageRead()
    {
        return $this->_data['message_read'];
    }
}
/* end of MessagesEntity.php */