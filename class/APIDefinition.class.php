<?php
class APIDefinition {
	
	const KEY_PARAMETERS = 'parameters';
	const KEY_FUNCTION = 'function';
	const KEY_DESCRIPTION = 'description';
	const KEY_INPUT = 'input';
	const KEY_DEFAULT = 'default';
	const KEY_REQUIRED = 'required';
	const KEY_COMMENT = 'comment';
	const KEY_OUTPUT = 'output';
	const KEY_IS_VARIABLE = 'is_variable';
	const KEY_IS_MULTIPLE = 'is_multiple';
	const KEY_CONTENT = 'content';
	const KEY_SOAP = 'soap';
	const KEY_SOAP_NAME = 'soap-name';
	
	private $api_definition_file_path; 
	private $ymlLoader;
	
	public function __construct($api_definition_file_path, YMLLoader $ymlLoader){
		$this->api_definition_file_path = $api_definition_file_path;
		$this->ymlLoader = $ymlLoader;
	}
	
	private function setDefaultValue(array & $array,$key_or_keys_array,$default){
		if (is_array($key_or_keys_array)){
			foreach($key_or_keys_array as $key){
				$this->setDefaultValue($array,$key,$default);
			}
		} elseif (! isset($array[$key_or_keys_array])){
			$array[$key_or_keys_array] = $default;
		}
	}
	
	public function getFunctions(){
		$api_definition = $this->ymlLoader->getArray($this->api_definition_file_path);
		$functions =  $api_definition[self::KEY_FUNCTION];
		foreach($functions as $name => $fonction){
			$functions[$name][self::KEY_SOAP_NAME] = $this->camelize($name);
			$this->setDefaultValue($functions[$name],array(self::KEY_INPUT,self::KEY_OUTPUT),array());
			$this->setDefaultValue($functions[$name],self::KEY_COMMENT,"");
			$this->setDefaultValue($functions[$name],self::KEY_SOAP,false);
			
			
			foreach($functions[$name][self::KEY_INPUT] as $param_name => $param_properties){
				if (empty($param_properties)){
					if (isset($api_definition[self::KEY_PARAMETERS][$param_name])){
						$functions[$name][self::KEY_INPUT][$param_name] = $api_definition[self::KEY_PARAMETERS][$param_name];
					}	
				}
				$this->setDefaultValue(	$functions[$name][self::KEY_INPUT][$param_name],self::KEY_DEFAULT,"");
				$this->setDefaultValue(	$functions[$name][self::KEY_INPUT][$param_name],'type', 'xsd:String');
				
			}
			
			foreach($functions[$name][self::KEY_OUTPUT] as $param_name => $param_properties){
				$this->setDefaultValue(	$functions[$name][self::KEY_OUTPUT][$param_name],array(self::KEY_IS_VARIABLE,self::KEY_IS_MULTIPLE),false);
				$this->setDefaultValue(	$functions[$name][self::KEY_OUTPUT][$param_name],self::KEY_CONTENT,array());
				$this->setDefaultValue(	$functions[$name][self::KEY_OUTPUT][$param_name],'type',"xsd:String");
				$this->setDefaultValue(	$functions[$name][self::KEY_OUTPUT][$param_name],'minOccurs', 0);
				$this->setDefaultValue(	$functions[$name][self::KEY_OUTPUT][$param_name],'maxOccurs', 1);
				
				
				foreach($functions[$name][self::KEY_OUTPUT][$param_name][self::KEY_CONTENT] as $content_name => $content_properties){
					$this->setDefaultValue($functions[$name][self::KEY_OUTPUT][$param_name][self::KEY_CONTENT][$content_name],array(self::KEY_IS_VARIABLE,self::KEY_IS_MULTIPLE),false);
					$this->setDefaultValue($functions[$name][self::KEY_OUTPUT][$param_name][self::KEY_CONTENT][$content_name],self::KEY_COMMENT,"");
				}
			}
		}
		return $functions;
	}
	
	public function getSoapFunctions(){
		$result = array();
		foreach($this->getFunctions() as $name => $properties){
			if ($properties[self::KEY_SOAP] ){
				$result[$name] = $properties;
			}
		}
		return $result;
		
	}
	
	private function camelize($name){
		$allName = explode("-",$name);
		$allName = array_map('ucfirst',$allName);
		$allName[0] = lcfirst($allName[0]);
		return implode("",$allName);
	} 
	
	public function getWSDL($location){
	$functions_list = $this->getSoapFunctions();
	$namespace= "http://s2low.sigmalis.com/service/1.3" ;
	
	ob_start();
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<wsdl:definitions 
	xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/" 
	xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
	xmlns:tns="<?php echo $namespace ?>" 
	xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/" 
	xmlns:soapenc="http://schemas.xmlsoap.org/soap/encoding/" 
	xmlns:http="http://www.w3.org/2003/05/soap/bindings/HTTP/"  
	xmlns:xop="http://www.w3.org/2004/08/xop/include"
	xmlns:wsp="http://schemas.xmlsoap.org/ws/2002/12/policy" 
	xmlns:wsam=" http://www.w3.org/2007/05/addressing/metadata"
	
	
	targetNamespace="<?php echo $namespace ?>" 	
	>
	<wsdl:types>
		<xsd:schema elementFormDefault="qualified" targetNamespace="<?php echo $namespace ?>" >
			<xsd:import namespace="http://www.w3.org/2004/08/xop/include" schemaLocation="http://www.w3.org/2004/08/xop/include"/>
			

			<?php foreach($functions_list as $function_name => $function_properties) : ?>
			<xsd:element name="<?php hecho($function_properties['soap-name'])?>">
				<xsd:complexType>
					<xsd:sequence>
						<?php foreach($function_properties[APIDefinition::KEY_OUTPUT] as $output_name => $output_properties)  : ?>
						<xsd:element name="<?php echo $output_name ?>" 
									type="<?php echo $output_properties['type'] ?>" 
									minOccurs="<?php echo $output_properties['minOccurs'] ?>"	
									maxOccurs="<?php echo $output_properties['maxOccurs'] ?>"/>
						<?php endforeach;?>
					</xsd:sequence>
				</xsd:complexType>
			</xsd:element>	
			<?php endforeach;?>		
		</xsd:schema>
	</wsdl:types>
	
	<?php foreach($functions_list as $function_name => $function_properties) : ?>
	<wsdl:message name="<?php hecho($function_properties['soap-name'])?>">
		<?php foreach($function_properties[APIDefinition::KEY_INPUT] as $name => $value): ?>
			<wsdl:part name="<?php hecho($name)?>" type="<?php echo $value['type'] ?>" />
		<?php endforeach;?>
	</wsdl:message>	
	<wsdl:message name="<?php hecho($function_properties['soap-name'])?>Response">
	<?php if ($function_properties[APIDefinition::KEY_OUTPUT] ) : ?>
		<wsdl:part name="return" element="tns:<?php hecho($function_properties['soap-name'])?>"/>
	<?php endif;?>
	</wsdl:message>
	<?php endforeach;?>
	
	<wsdl:portType name="s2lowSoap">
	<?php foreach($functions_list as $function_name => $function_properties) : ?>
		<wsdl:operation name="<?php hecho($function_properties['soap-name'])?>">
			<wsdl:input message="tns:<?php hecho($function_properties['soap-name'])?>"/>
			<wsdl:output message="tns:<?php hecho($function_properties['soap-name'])?>Response"/>
		</wsdl:operation>	
	<?php endforeach;?>
	</wsdl:portType>
	
	<wsdl:binding name="s2lowSoap" type="tns:s2lowSoap">		
		<soap:binding transport="http://schemas.xmlsoap.org/soap/http" style="rpc"/>
		<?php foreach($functions_list as $function_name => $function_properties) : ?>
			<wsdl:operation name="<?php hecho($function_properties['soap-name'])?>">
				<soap:operation soapAction="<?php echo $namespace?>/<?php hecho($function_properties['soap-name'])?>" />
				<wsdl:input>
					<soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="<?php echo $namespace?>"/>
				</wsdl:input>
				<wsdl:output>
					<soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="<?php echo $namespace?>" parts="return"/>
				</wsdl:output>
			</wsdl:operation>
		<?php endforeach;?>		
	</wsdl:binding>
	
	<wsdl:service name="s2low">
		<wsdl:port name="s2lowSoap" binding="tns:s2lowSoap">
			<soap:address location="<?php echo $location ?>"/>
		</wsdl:port>
	</wsdl:service>
</wsdl:definitions>
	<?php 
	return ob_get_clean();
	}
	
}