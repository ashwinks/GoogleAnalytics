<?php

    class GoogleAnalytics {

        private $gatId;
        private $customVars = array();

        public function __construct($gatId){

            if (empty($gatId)) throw new Exception("Invalid GA Profile ID {$gatId}");

            $this->gatId = trim($gatId);
        }

        /**
         *
         * This should only be used if we're manually passing campaign information through a url
         *
         * For regular page tracking use @method getBasicInitCode
         *
         * @param string $utm_source
         * @param string $utm_medium
         * @param string $utm_campaign
         * @param string $utm_content
         * @param string $utm_term
         */
        public function getManualCampaignInitCode($utm_source, $utm_medium, $utm_campaign, $utm_content, $utm_term, $referrer = ''){

            // init gat
            $code = $this->getGaqInitCode();

            if (!empty($referrer)){
                $code .= $this->getReferrerOverrideCode($referrer);
            }

            // tack on any custom vars that we may have set
            if (count($this->customVars) > 0){
                foreach ($this->customVars as $customVar){
                    $code .= $this->getCustomVarCode($customVar->index, $customVar->name, $customVar->value, $customVar->scope);
                }
            }

            // overrid our __utmz campaign cookie
            $code .= $this->getSetCampaignVarsCode($utm_source, $utm_medium, $utm_campaign, $utm_content, $utm_term);

            // track the page view
            $code .= $this->getTrackPageViewCode();

            // load ga.js
            $code .= $this->getGaJsCode();

            // wrap the entire code in javascript tags
            $code = $this->wrapCodeInScriptTags($code);

            return $code;
        }

        /**
         * Returns simple google analytics code
         * It just sets the account and tracks a page view
         */
        public function getBasicInitCode(){

            // init gat
            $code = $this->getGaqInitCode();

            // add custom vars if there are any
            if (count($this->customVars) > 0){
                foreach ($this->customVars as $customVar){
                    $code .= $this->getCustomVarCode($customVar->index, $customVar->name, $customVar->value, $customVar->scope);
                }
            }

            // track the pageview
            $code .= $this->getTrackPageViewCode();

            // load ga.js
            $code .= $this->getGaJsCode();

            // wrap code in javascript tags
            $code = $this->wrapCodeInScriptTags($code);

            return $code;
        }

        /**
         * Returns javascript code to track an event
         *
         * @param string $category 	The name you supply for the group of objects you want to track.
         * @param string $action 		A string that is uniquely paired with each category, and commonly used to define the type of user interaction for the web object.
         * @param string $label		An optional string to provide additional dimensions to the event data.
         * @param string $value		An integer that you can use to provide numerical data about the user event.
         * @param bool $wrapInScriptTags	Whether or not to wrap the js code in script tags
         */
        public static function getEventCode($category, $action, $label = '', $value = null, $wrapInScriptTags = false){

            if (empty($category)){
                throw new Exception("Invalid category: {$category}");
            }

            if (empty($action)){
                throw new Exception("Invalid action: {$action}");
            }

            $code = "_gaq.push(['_trackEvent', '{$category}', '{$action}', '{$label}'";
            if ($value){
                $value = (int) $value;
                $code .= ", {$value}";
            }
            $code .= "]);";

            if ($wrapInScriptTags){
                $code = $this->wrapCodeInScriptTags($code);
            }

            return $code;
        }

        /**
         *
         * Get code to track a virtual pageview
         *
         * @param string $url URL to track
         * @param bool $wrapInScriptTags Whether or not to wrap the js code in script tags
         */
        public function getVirtualPageviewCode($url = null, $wrapInScriptTags = false){

            if (!$url){
                throw new Exception("Invalid URL to track");
            }

            $code = $this->getTrackPageViewCode($url);

            if ($wrapInScriptTags){
                $code = $this->wrapCodeInScriptTags($code);
            }

            return $code;

        }

        public function trackVirtualPageview($url, $wrapInScriptTags){

            echo $this->getVirtualPageviewCode($url, $wrapInScriptTags);

        }

         /**
         *
         * Set a GAT custom variable
         * Must be called prior to calling @method getBasicInitCode or @method getReferrerOverrideInitCode
         *
         * @param int $index	The slot for the custom variable. Required.
         * 						This is a number whose value can range from 1 - 5, inclusive.
         * 						A custom variable should be placed in one slot only and not be re-used across different slots.
         *
         * @param string $name	The name for the custom variable. Required.
         * 						This is a string that identifies the custom variable and appears in the top-level
         * 						Custom Variables report of the Analytics reports.
         *
         * @param string $value	The value for the custom variable. Required.
         * 						This is a string that is paired with a name.
         * 						You can pair a number of values with a custom variable name.
         * 						The value appears in the table list of the UI for a selected variable name.
         * 						Typically, you will have two or more values for a given name.
         * 						For example, you might define a custom variable name gender
         * 						and supply male and female as two possible values.
         *
         * @param int $scope	The scope for the custom variable. Optional.
         * 						As described above, the scope defines the level of user engagement with your site.
         * 						It is a number whose possible values
         * 						are 1 (visitor-level), 2 (session-level), or 3 (page-level).
         * 						When left undefined, the custom variable scope defaults to page-level interaction.
         * @param bool $wrapInScriptTags	Whether or not to wrap the js code in script tags
         */
        public function setCustomVar($index, $name, $value, $scope = 3){

            if (empty($index)){
                throw new Exception("Invalid index: {$index}");
            }

            if (empty($name)){
                throw new Exception("Invalid name: {$name}");
            }

            if (empty($value)){
                throw new Exception("Invalid value: {$value}");
            }

            $obj = new stdClass();
            $obj->index = $index;
            $obj->name = $name;
            $obj->value = $value;
            $obj->scope = $scope;

            array_push($this->customVars, $obj);

        }

        private function wrapCodeInScriptTags($code){

            if (empty($code)){
                throw new Exception("No code to wrap in script tags");
            }

            $code = '<script type="text/javascript">' . $code . '</script>';

            return $code;

        }

        private function getGaqInitCode(){

            $code = "var _gaq = _gaq || [];";
            $code .= "_gaq.push(['_setAccount', '{$this->gatId}']);";

            return $code;
        }

        private function getGaJsCode(){

            $code = "(function() {var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);})();";

            return $code;

        }

        private function getReferrerOverrideCode($referrer){

            $referrer = trim($referrer);

            if (empty($referrer)){
                throw new Exception("Invalid Referrer");
            }

            $code = "_gaq.push(['_setReferrerOverride', '{$referrer}']);";

            return $code;
        }

        private function getCookieModificationCode(){

            $code = 'function Utmz(a){this.v=unescape(a);this.sr="(direct)";this.cn="(direct)";this.cmd="(none)";this.s="utmcsr="+this.sr+"|utmccn="+this.cn+"|utmcmd="+this.cmd;if(a!=null){this.s=a.replace(/^[0-9\.]*/,"");a.replace(/utmcsr=([^\|]*)\|utmccn=([^\|]*)\|utmcmd=([^|]*)/,function(){this.sr=arguments[1];this.cn=arguments[2];this.cmd=arguments[3]})}this.sv=function(){extga._sc("__utmz",this.v,182)};this.isNew=function(){return this.v=="null"};this._setCampName=function(b){this.v=this.v.replace(/utmccn=([^\|]*)/,"utmccn="+b);this.sv()};this._setCampSource=function(b){this.v=this.v.replace(/utmcsr=([^\|]*)/,"utmcsr="+b);this.sv()};this._setCampMedium=function(b){this.v=this.v.replace(/utmcmd=([^\|]*)/,"utmcmd="+b);this.sv()};this._setCampTerm=function(b){this.v=this.v.match(/utmctr=/)?this.v.replace(/utmctr=([^\|]*)/,"utmctr="+b):this.v+"|utmctr="+b;this.sv()};this._setCampContent=function(b){this.v=this.v.match(/utmcct=/)?this.v.replace(/utmcct=([^|]*)/,"utmcct="+b):this.v+"|utmcct="+b;this.sv()};this._reset=function(){this.v=this.v.replace(/^([0-9\.]*).*$/,"$1utmcsr=(direct)|utmccn=(direct)|utmcmd=(none)")}}var extga={_fm:false,_fr:false,_rc:function(b){var c=new RegExp(b+"=([^;]*)","i");var a=document.cookie.match(c);return(a&&a.length==2)?a[1]:null},_Ua:function(a){if(a!=""){return" domain="+a}else{if(document.domain.match(/^www/)!=null){return" domain="+document.domain.replace(/^www/,"")}else{return" domain="+document.domain}}},_sc:function(g,i,h){var a=new Date();a.setTime(a.getTime()+(((typeof(h)!="undefined")?h:3)*24*60*60*1000));var d=g+"="+i+"; expires="+a.toGMTString()+"; path=/;"+this._Ua(this.domain);document.cookie=d},_reset:false,_setCampValues:function(l,d,i,c,j,k){extga.domain=k||"";extga.outmz=new Utmz(extga._rc("__utmz"));_gaq.push(["_initData"]);extga.nutmz=new Utmz(extga._rc("__utmz"));if(extga.outmz.s!=extga.nutmz.s){extga._fr=true;extga.outmz=new Utmz(extga._rc("__utmz"))}else{if(extga.outmz.isNew()){extga._direct=true}}if(extga._getCampValues().medium=="referral"){extga._fm=true}if(extga._fm||!extga._fr){if(extga._reset){extga.nutmz._reset()}if(l){extga.nutmz._setCampSource(l)}if(d){extga.nutmz._setCampMedium(d)}if(i){extga.nutmz._setCampName(i)}if(c){extga.nutmz._setCampTerm(c)}if(j){extga.nutmz._setCampContent(j)}}},_getCampValues:function(){var b={sr:"source",cn:"name",md:"medium",ct:"content",tr:"term"};var c=unescape(extga._rc("__utmz"));var a={source:"",medium:"",name:"",term:"",content:"",isDirect:function(){return(a.content==""&&a.medium=="(none)"&&a.name=="(direct)"&&a.source=="(direct)"&&a.term=="")},isOrganic:function(){return(a.medium=="organic"&&a.name=="(organic)")},isCampaign:function(d){var e=new RegExp("("+d+")");return a.name.match(e)!=null}};if(c!=null){c.replace(/utmc([a-z]{2})=([^\|]*)/g,function(d,f,e){a[b[f]]=e})}return a}};';

            return $code;
        }

        private function getSetCampaignVarsCode($utm_source, $utm_medium, $utm_campaign, $utm_content, $utm_term){

            $utm_source = urlencode(trim($utm_source));
            $utm_medium = urlencode(trim($utm_medium));
            $utm_campaign = urlencode(trim($utm_campaign));
            $utm_content = urlencode(trim($utm_content));
            $utm_term = urlencode(trim($utm_term));

            if (empty($utm_campaign)){
                throw new Exception("Invalid utm_campaign: {$utm_campaign}");
            }

            if (empty($utm_source)){
                throw new Exception("Invalid utm_source: {$utm_source}");
            }

            if (empty($utm_medium)){
                throw new Exception("Invalid utm_medium: {$utm_medium}");
            }

            if (empty($utm_content)){
                throw new Exception("Invalid utm_content: {$utm_content}");
            }

            $code = $this->getCookieModificationCode();

            $code .= '_gaq.push(function() {extga._setCampValues(\'' . $utm_source . '\',\'' . $utm_medium . '\',\'' . $utm_campaign . '\',\'' . $utm_term . '\',\'' . $utm_content . '\');});';

            return $code;
        }

        private function getTrackPageViewCode($url = null){

            if (!$url){
                $code = "_gaq.push(['_trackPageview']);";
            }else{
                $code = "_gaq.push(['_trackPageview', '{$url}']);";
            }

            return $code;

        }

         /**
         *
         * Set a GAT custom variable
         *
         * @param int $index	The slot for the custom variable. Required. This is a number whose value can range from 1 - 5, inclusive. A custom variable should be placed in one slot only and not be re-used across different slots.
         * @param string $name	The name for the custom variable. Required. This is a string that identifies the custom variable and appears in the top-level Custom Variables report of the Analytics reports.
         * @param string $value	The value for the custom variable. Required. This is a string that is paired with a name. You can pair a number of values with a custom variable name. The value appears in the table list of the UI for a selected variable name. Typically, you will have two or more values for a given name. For example, you might define a custom variable name gender and supply male and female as two possible values.
         * @param int $scope	The scope for the custom variable. Optional. As described above, the scope defines the level of user engagement with your site. It is a number whose possible values are 1 (visitor-level), 2 (session-level), or 3 (page-level). When left undefined, the custom variable scope defaults to page-level interaction.
         * @param bool $wrapInScriptTags	Whether or not to wrap the js code in script tags
         */
        private function getCustomVarCode($index, $name, $value, $scope = 3){

            if (empty($index)){
                throw new Exception("Invalid index: {$index}");
            }

            if (empty($name)){
                throw new Exception("Invalid name: {$name}");
            }

            if (empty($value)){
                throw new Exception("Invalid value: {$value}");
            }

            $code = "_gaq.push(['_setCustomVar', {$index}, '{$name}', '{$value}', {$scope}]);";

            return $code;

        }

        /**
         *
         * Constructs and sends the social tracking call to the Google Analytics Tracking Code.
         * Use this to record clicks on social sharing buttons on your website other than the Google +1 button (for which reporting is pre-configured).
         *
         * @param string $network 			The network on which the action occurs (e.g. Facebook, Twitter)
         *
         * @param string $socialAction 		The type of action that happens (e.g. Like, Send, Tweet).
         *
         * @param string $target Optional. 	The text value that indicates the subject of the action; most typically this is a URL target. If undefined, defaults to document.location.href.
         * 									For example, if a user clicks the Facebook Like button for a news article, the URL for that news article could be sent as the string.
         * 									You could also supply any other ID identifying the target, such as an ID from your content management system.
         * 									As another example, a user could click the Like button next to a blog post, in which case no value need be sent for this parameter because the current document location URL is sent by default.
		 *									Note: If you want to leave this value undefined at the same time you want to supply a value for the next parameter (opt_pageUrl), you must specify undefined in your code.
         *
         * @param string $pagePath			Optional. The page (by path, not full URL) from which the action occurred.
         * 									If undefined, defaults to document.location.pathname plus document.location.search.
         * 									You will only need to supply a value for this string if you use virtual (or custom) page paths for Analytic reporting purposes.
         * 									When using this option, use a beginning slash (/) to indicate the page URL. For more information, see "Virtual URLs" in Typical Customizations.
         * @throws Exception
         */
        private function getTrackSocialCode($network, $socialAction, $target, $pagePath){

            if (empty($network)){
                throw new Exception("Invalid network: {$network}");
            }

            if (empty($socialAction)){
                throw new Exception("Invalid socialAction: {$socialAction}");
            }

            $code = "_gaq.push(['_trackSocial', '{$network}', '{$socialAction}'";
            if (!empty($target) && empty($pagePath)){
                $code .= ",'{$target}'";
            }

            if (!empty($pagePath)){

                if (empty($target)){
                    $target = 'undefined';
                }

                $code .= ",'{$target}', '{$pagePath}'";

            }

            $code .= "]);";

            return $code;

        }

    }