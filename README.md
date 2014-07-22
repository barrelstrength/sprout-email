Requirements:   

- Integration file must be placed inside the plugins/<plugin>/integrations/sproutemail directory
- Integration file must be named SproutEmail_<pluginName>.php
- Integration class must be named SproutEmail_<pluginName>
- Integration class must implement the getHooks() method returning the following array:
		array(
			'commerceAddEventListener' => array( // plugin's hook to be called
				'hooks' => array( 
					array(
						'event' => 'checkoutEnd',
						'description' => 'Commerce: when an order is submitted',
						'optionsTemplate' => '', // template with event options
						'optionsHtml' => 
							'<hr>
							<h3>Custom options for Commerce.</h3>
							
							{% set opts = campaign.notificationEvents[0].options %}

							{{ forms.textField({
								label: "Notification Name"|t,
								id: "wtf",
								name: "options[wtf]",
								instructions: "test"|t,
								value: (opts.wtf is defined ? opts.wtf : null),
								autofocus: true,
							}) }}
							<hr>', // if html provided, will be used regardless of optionsTemplate
						'optionsHandler' => 'commerce_sproutEmail::testIt' // can be a function in main plugin file or a service
					),
				)
			)
- Plugin must expose a hook registration function which accepts the event name and an anonymous callback function,
e.g.:
public function commerceAddEventListener($event, \Closure $callback)

- The anonymous function accepts three params, of which the first two MUST be passed: String $event, BaseModel $entity
The function signature is: return function($event, BaseModel $entity, $success = TRUE)

- If the plugin will have options, it needs to provide a service and function to filter the request; it
will be passed three params: String $event, BaseModel $entry, array $options
e.g.

If the function returns false, the notification will NOT be sent 


