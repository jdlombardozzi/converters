<?php
	class acp
	{
		/**
	     * Show Error Message
	     *
	     * @access	private
	     * @param	string		Error message
	     * @return	void
	    */
		public function sendError($message)
		{
			ipsRegistry::getClass('output')->showError( $message );
		}

		/**
	     * Display Menu
	     *
	     * @access	private
	     * @param	array		Array containing special infomation if converters should automatically move to next step
	     * @return	void
	    */
		protected function menu($special=array())
		{
			$actionrows = array();
			foreach ($this->module->actions as $action => $pres)
			{
				if ( !empty($special) and array_key_exists($action, $special) )
				{
					$count = 0;
					foreach($special[$action]['multi'] as $multicount)
					{
						$count += $this->countRows($multicount);
					}

					$actionrows[] = $this->html->convertMenuRow($this->menuRow($action), $this->module->countRows($special[$action]['single']), $this->getStatus($action), $this->getButton($action, $this->module->actions, $this->module->checkConf($action)), $count);
				}
				else
				{
					$actionrows[] = $this->html->convertMenuRow($this->menuRow($action), $this->module->countRows($action), $this->getStatus($action), $this->getButton($action, $this->module->actions, $this->module->checkConf($action)));
				}
			}
			$this->registry->output->html .= $this->html->convertMenu(implode('', $actionrows), $this->getInfo());
		}

		/**
		 * Return a button
		 *
		 * @access 	public
		 * @param	string		action (e.g. 'members', 'forums', etc.)
		 * @param 	array 		actions that converter is capable of
		 * @param 	boolean		If true, actions has configuration options
		 * @return 	string 		html to display
		 **/
		private function getButton($action, $actions, $conf)
		{
			$info = $this->menuRow($action);
			$on = true;
			$missed = array();
			if (is_array($actions[$action]))
			{
				foreach ($actions[$action] as $pre)
				{
					if ($this->getStatus($pre) == '-' and $this->getStatus($pre, true) == '-')
					{
						$on = false;
					}
				}
			}
			if ($on)
			{
				if ($this->getStatus($action) == '-')
				{
					return $this->html->convertMenuRowButtonOn();
				}
				else
				{
					return $this->html->convertMenuRowButtonAgain($conf);
				}
			}
			else
			{
				foreach ($actions[$action] as $ppre)
				{
					$ipre = $this->menuRow($ppre, true);
					$pres[] = ($ipre) ? $ipre['name'] : ucwords(str_replace('_', ' ', $ppre));
				}
				return $this->html->convertMenuRowButtonOff($pres);
			}
		}

		/**
	     * Move to next step automatically
	     *
		 * @param 	string 		Action to do next
	     * @return	void
	    */
		public function goToNext($next)
		{
			if(is_array($next))
			{
				$this->registry->output->html .= $this->registry->output->global_template->temporaryRedirect("{$this->settings['base_url']}app=convert&module={$this->app['sw']}&section={$this->app['app_key']}&do={$next[0]}&st=0&cycle={$this->request['cycle']}&total=".$this->countRows($next[1]), "Continuing..." );
			}
			else
			{
				$this->registry->output->html .= $this->registry->output->global_template->temporaryRedirect("{$this->settings['base_url']}app=convert&module={$this->app['sw']}&section={$this->app['app_key']}&do={$next}&st=0&cycle={$this->request['cycle']}&total=".$this->countRows($next), "Continuing..." );
			}
			$this->sendOutput ( );
		}

		/**
	     * Display Finish Screen
	     *
	     * @access	private
		 * @param 	array 		Info from menuRow();
	     * @return	void
	    */
		protected function displayFinishScreen($info)
		{
			if (!empty($this->errors))
			{
				$es = 'The following errors occurred: <ul>';
				foreach ($this->errors as $e)
				{
					$es .= "<li>{$e}</li>";
				}
				$es .= '</ul>';
			}
			else
			{
				$es = 'No problems found.';
			}

			$this->registry->output->html .= $this->html->convertComplete($info['name'].' Conversion Complete.', array($es, $info['finish']));
			$this->sendOutput();
		}

		/**
		 * Reloads current cycle
		 *
		 * @access 	public
		 * @return void
		 **/
		public function reload()
		{
			$this->registry->output->html .= $this->registry->output->global_template->temporaryRedirect("{$this->settings['base_url']}app=convert&module={$this->app['sw']}&section={$this->app['app_key']}&do={$this->request['do']}&st={$this->start}&cycle={$this->request['cycle']}&total={$this->request['total']}", "Loading..." );
			$this->sendOutput ( );
		}

		/**
		 * Load anything needed for the interface
		 *
		 * @access	public
		 * @return 	mixed 	Anything needed for the interface
		 **/
		public function loadInterface()
		{
			return $this->registry->output->loadTemplate( 'cp_skin_convert' );
		}
	}

?>