<?php
namespace IntegrationRay\TestSession;


use Structura\Set;
use IntegrationRay\Exception\FatalEngineException;


class SessionSetupCollection
{
	private $setupObjects = [];
	
	
	private function getSessionOnly(string $name): ISessionSetup
	{
		if (!isset($this->setupObjects[$name]))
			throw new FatalEngineException("Session config named '{$name}' not found");
		
		return $this->setupObjects[$name];
	}
	
	
	/**
	 * @param string $sessionName
	 * @return ISessionSetup[]
	 */
	public function getSessions(string $sessionName): array
	{
		$main		= $this->getSessionOnly($sessionName);
		$toLoad		= new Set($main->dependencies());
		$loaded		= new Set();
		$sessions	= [];
		
		while ($toLoad)
		{
			$butch = clone $toLoad;
			
			foreach ($toLoad->toArray() as $value)
			{
				$session = $this->getSessionOnly($value);
				$dependencies = $session->dependencies();
				
				if (!$dependencies || $loaded->hasAll($dependencies))
				{
					$sessions[] = $session;
					$loaded->add($value);
				}
				else
				{
					$toLoad->add($dependencies);
				}
			}
			
			if ($butch->count() == $toLoad->count() && $butch->hasAll($toLoad))
			{
				throw new FatalEngineException('Recursive dependency detected in one of: ' . 
					implode(', ', $toLoad->toArray()));
			}
		}
		
		$sessions[] = $main;
		
		return $sessions;
	}
	
	/**
	 * @param ISessionSetup $setup
	 */
	public function add(ISessionSetup $setup): void
	{
		$name = $setup->name();
		
		if (isset($this->setupObjects[$name]) && $name != 'homepage')
		{
			$existingClass = get_class($this->setupObjects[$name]);
			$newClass = get_class($setup);
			
			throw new FatalEngineException(
				"Session config with the name {$name} already defined. " . 
				"Existing class: $existingClass, new class: $newClass"
			);
		}
		
		$this->setupObjects[$name] = $setup;
	}
}