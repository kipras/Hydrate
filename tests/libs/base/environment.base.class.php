<?php

class TestEnvironmentBase
{
    protected $_sandboxStarted = FALSE;
    
    public function before()
    {
        // Do any initialization for your system environment here..
        register_shutdown_function(Array($this, "after"));
        
        if (method_exists($this, "beforeCustom"))
            $this->beforeCustom();
        
        $this->sandboxStart();
    }
    
    public function after()
    {
        if ($this->_sandboxStarted)
            $this->sandboxEnd();
        
        if (method_exists($this, "afterCustom"))
            $this->afterCustom();
    }
    
    public function sandboxStart()
    {
        if ($this->_sandboxStarted)
            $this->sandboxEnd();
        
        if (method_exists($this, "sandboxStartCustom"))
            $this->sandboxStartCustom();
        
        $this->_sandboxStarted = TRUE;
    }
    
    public function sandboxEnd()
    {
        if (method_exists($this, "sandboxEndCustom"))
            $this->sandboxEndCustom();
        
        $this->_sandboxStarted = FALSE;
    }
}
