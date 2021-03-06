<?php

class Lemonline_Salescontacts_IndexController extends Mage_Core_Controller_Front_Action
{

    const XML_PATH_EMAIL_RECIPIENT  = 'contacts/salesemail/recipient_email';
    const XML_PATH_EMAIL_SENDER     = 'contacts/salesemail/sender_email_identity';
    const XML_PATH_EMAIL_TEMPLATE   = 'contacts/salesemail/email_template';
    const XML_PATH_ENABLED          = 'contacts/salescontacts/enabled';

    public function preDispatch()
    {
        parent::preDispatch();

        if( !Mage::getStoreConfigFlag(self::XML_PATH_ENABLED) ) {
            $this->norouteAction();
        }
    }

    public function indexAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('salescontactform')
            ->setFormAction( Mage::getUrl('*/*/send') );

        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');
        $this->renderLayout();
    }

    public function sendAction()
    {
        $post = $this->getRequest()->getPost();
        if ( $post ) {
            $translate = Mage::getSingleton('core/translate');
            $translate->setTranslateInline(false);
            try {
                $postObject = new Varien_Object();
                $postObject->setData($post);

                $error = false;

                if (!Zend_Validate::is(trim($post['name']) , 'NotEmpty')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['description']) , 'NotEmpty')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['email']), 'EmailAddress')) {
                    $error = true;
                }

                if (Zend_Validate::is(trim($post['hideit']), 'NotEmpty')) {
                    $error = true;
                }

                if ($error) {
                    throw new Exception();
                }
                $mailTemplate = Mage::getModel('core/email_template');
                $mailTemplate->setDesignConfig(array('area' => 'frontend'))
                    ->setReplyTo($post['email'])
                    ->sendTransactional(
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT),
                        null,
                        array('data' => $postObject)
                    );

                if (!$mailTemplate->getSentSuccess()) {
                    throw new Exception();
                }

                $translate->setTranslateInline(true);

                Mage::getSingleton('customer/session')->addSuccess(Mage::helper('salescontacts')->__('Thanks for your enquiry. We will be in touch shortly!'));
                $this->_redirect('*/*/');

                return;
            } catch (Exception $e) {
                $translate->setTranslateInline(true);

                Mage::getSingleton('customer/session')->addError(Mage::helper('salescontacts')->__('Sorry, we were unable to submit your enquiry.'));
                $this->_redirect('*/*/');
                return;
            }

        } else {
            $this->_redirect('*/*/');
        }
    }

}
