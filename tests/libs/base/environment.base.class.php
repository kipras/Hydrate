<?php

class TestEnvironmentBase
{
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
        $this->sandboxEnd();
        
        if (method_exists($this, "afterCustom"))
            $this->afterCustom();
    }
    
    private function sandboxStart()
    {
        if (method_exists($this, "sandboxStartCustom"))
            $this->sandboxStartCustom();
    }
    
    private function sandboxEnd()
    {
        if (method_exists($this, "sandboxEndCustom"))
            $this->sandboxEndCustom();
    }
}
