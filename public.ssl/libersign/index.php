 
<applet codebase = "https://localhost:4443/libersign/"
		code = "org/adullact/parapheur/applets/splittedsign/Main.class" 
		archive = "SplittedSignatureApplet.jar, lib/bcmail-jdk16-138.jar, lib/bcprov-jdk16-138.jar, lib/xom-1.1.jar" 
		name = "appletsignature"
		width = "500"
		height = "257" >
	<param name="hash_count" value="1" />
	<param name="iddoc_1" value="doc1" />
	<param name="hash_1" value="73f227f21065058733cf719533e860d66f04f7b8" /> 
	<param name="format_1" value="CMS" />
	<param name="url_send_content" value="http://localhost:8888-/adullact/test/test.php" /> 
	<param name="id_user" value="id=1" />
	
	<param name="return_mode" value="http" />
 </applet>