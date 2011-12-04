<?php
/**
 * TODO: Docs.
 */
class DomElCollection {
   private $_dom;
   private $_elArray;
   /**
    * Stores a collection of DomEl objects, accessible as an inexed array.
    * @param array $elArray An array containing either DomEl objects or PHP's
    * native DOMElement objects, that will be automatically converted into
    * DomEl objects.
    */
   public function __construct($dom, $elArray = array()) {
      $this->_dom = $dom;

      if(!is_array($elArray)) {
         // Possible to only pass a single DOMElement or DomEl object as param.
         if($elArray instanceof DOMElement) {
            $elArray = array($dom->createElement($elArray));
         }
         else if($elArray instanceof DomEl) {
            $elArray = array($elArray);
         }
         else if($elArray instanceof DOMNodeList) {
            $list = $elArray;
            $listLength = $elArray->length;

            $elArray = array();
            for($i = 0; $i < $listLength; $i++) {
               $elArray[] = $dom->createElement($list->item($i));
            }
         }
         else {
            var_dump($elArray);
            // TODO: Proper error logging and output.
            die("Error creating DomElCollection.");
         }
      }
      $this->_elArray= $elArray;
   }

   /**
    * Calls the given function on each DomEl in the stored element array.
    * @return mixed The result of calling the function on the last element in
    * the collection.
    */
   public function __call($name, $args) {
      $result = null;
      foreach($this->_elArray as $el) {
         if(is_callable(array($el, $name)) ) {
            $result = call_user_func_array(array($el, $name), $args);
         }
      }

      return $result;
   }

   /**
    * Returns the requested property from the first contained element. This
    * allows for a more natrual coding style when using CSS selectors to work
    * with selectors only matching one element i.e. $dom["p#main"]->innerText
    * @param string $key The property name to retrieve.
    * @return mixed The value of the requested property.
    */
   public function __get($key) {
      if(count($this->_elArray) < 1) {
         // TODO: Properly log and throw error.
         die("Error: DomElCollection is empty.");
         return;
      }
      
      return $this->_elArray[0]->$key;
   }

   /**
    * Sets the property named $key of elements within the collection with the 
    * provided value.
    * @param string $key The property to set.
    * @param mixed $value The value to assign to the given property.
    */
   public function __set($key, $value) {
      foreach($this->_elArray as $el) {
         $el->$key = $value;
      }
   }
}
?>