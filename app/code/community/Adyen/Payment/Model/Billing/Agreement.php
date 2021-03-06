<?php
/**
 *                       ######
 *                       ######
 * ############    ####( ######  #####. ######  ############   ############
 * #############  #####( ######  #####. ######  #############  #############
 *        ######  #####( ######  #####. ######  #####  ######  #####  ######
 * ###### ######  #####( ######  #####. ######  #####  #####   #####  ######
 * ###### ######  #####( ######  #####. ######  #####          #####  ######
 * #############  #############  #############  #############  #####  ######
 *  ############   ############  #############   ############  #####  ######
 *                                      ######
 *                               #############
 *                               ############
 *
 * Adyen Payment Module
 *
 * Copyright (c) 2019 Adyen B.V.
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Adyen <magento@adyen.com>
 */
/**
 * @category   Payment Gateway
 * @package    Adyen_Payment
 * @author     Adyen
 * @property   Adyen B.V
 * @copyright  Copyright (c) 2015 Adyen BV (http://www.adyen.com)
 */


/**
 * Class Adyen_Payment_Model_Billing_Agreement
 */
class Adyen_Payment_Model_Billing_Agreement
    extends Mage_Sales_Model_Billing_Agreement
{

    public function parseRecurringContractData($data)
    {
        /** @var Adyen_Payment_Model_Adyen_Oneclick $methodInstance */
        $methodInstance = Mage::helper('payment')->getMethodInstance('adyen_oneclick');
        if (!$methodInstance) {
            Adyen_Payment_Exception::throwException('Can not update billing agreement, incorrect payment method specified in recurring contract data');
        }

        $methodInstance->parseRecurringContractData($this, $data);
        $this->setAgreementData($data);

        return $this;
    }


    public function setAgreementData($data)
    {
        if (is_array($data)) {
            unset($data['creationDate']);
            unset($data['recurringDetailReference']);
            unset($data['payment_method']);
        }

        $this->setData('agreement_data', json_encode($data));
        return $this;
    }

    public function getOneClickData()
    {
        $data = is_array($this->getAgreementData()) ? $this->getData() + $this->getAgreementData() : $this->getData();
        $data['title'] = $data['agreement_label'];
        unset($data['agreement_data']);
        unset($data['agreement_label']);

        return $data;
    }

    public function getAgreementData()
    {
        return json_decode($this->getData('agreement_data'), true);
    }


    /**
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomer()
    {
        if (!$this->hasData('customer')) {
            $customer = Mage::getModel('customer/customer')->load($this->getCustomerId());
            $this->setData('customer', $customer);
        }

        return $this->getData('customer');
    }


    /**
     * @return mixed
     */
    public function getCustomerReference()
    {
        if (!$this->hasData('customer_reference')) {
            $customerReference = $this->getCustomer()->getData('adyen_customer_ref')
                ?: $this->getCustomer()->getData('increment_id')
                    ?: $this->getCustomerId();
            $this->setData('customer_reference', $customerReference);
        }

        return $this->getData('customer_reference');
    }

    /**
     * Payment method instance
     *
     * @var Mage_Payment_Model_Method_Abstract
     */
    protected $_paymentMethodInstance = null;

    /**
     * Retrieve payment method instance
     *
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function getPaymentMethodInstance()
    {
        if (is_null($this->_paymentMethodInstance)) {
            $methodCode = $this->getMethodCode();
            if ($this->getMethodCode() == 'adyen_oneclick') {
                $referenceId = $this->getReferenceId();
                $methodInstanceName = $methodCode . "_" . $referenceId;
            } else {
                $methodInstanceName = $methodCode;
            }

            $this->_paymentMethodInstance = Mage::helper('payment')->getMethodInstance($methodInstanceName);

            if (!$this->_paymentMethodInstance) {
                $this->_paymentMethodInstance = Mage::helper('payment')->getMethodInstance($this->getMethodCode());
            }
        }

        if ($this->_paymentMethodInstance) {
            $this->_paymentMethodInstance->setStore($this->getStoreId());
        }

        return $this->_paymentMethodInstance;
    }

}
