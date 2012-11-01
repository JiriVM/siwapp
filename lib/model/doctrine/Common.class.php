<?php

/**
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class Common extends BaseCommon
{
  private $decimals = null;
  public function getRoundedAmount($concept = 'gross')
  {
    if(!in_array($concept,array('base','discount','net','tax','gross')))
    {
      return 0;
    }
    return Tools::getRounded(parent::_get($concept.'_amount'),$this->getDecimals());
  }
  private function getDecimals()
  {
    if(!$this->decimals)
    {
      $this->decimals = PropertyTable::get('currency_decimals',2);
    }
    return $this->decimals;
  }
  public function calculate($field,$rounded = false)
  {
    $val = 0;
    
    switch($field)
    {
      case 'paid_amount':
        foreach ($this->getPayments() as $payment)
        {
          $val += $payment->getAmount();
        }
        break;

      default:
        foreach ($this->getItems() as $item)
        {
          $method = 'get'.sfInflector::camelize($field);
          $val += $item->$method();
        }
        break;
    }
    
    if($rounded)
    {
      return round($val,$this->getDecimals());
    }

    return $val;
  }
  
  public function preSave($event)
  {
    $this->checkStatus();
    // check for customer matching
    Doctrine::getTable('Customer')->updateCustomer($this);
  }
  
  public function postDelete($event)
  {
      //    $this->Items->delete();
    
    
/*  and it�s over. clients shouldn�t be deleted after their last invoice. 
/*  see http://dev.markhaus.com/projects/siwapp/ticket/503
*/
  }
  
  public function setAmounts()
  {
    $this->setBaseAmount($this->calculate('base_amount'));
    $this->setDiscountAmount($this->calculate('discount_amount'));
    $this->setNetAmount($this->getBaseAmount() - $this->getDiscountAmount());
    $this->setTaxAmount($this->calculate('tax_amount'));
    $rounded_gross = round(
                           $this->getNetAmount() + $this->getTaxAmount(), 
                           PropertyTable::get('currency_decimals', 2)
                           );
    $this->setGrossAmount($rounded_gross);
    
    return $this;
  }
}
