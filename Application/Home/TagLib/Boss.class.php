<?php
namespace Home\TagLib;
use Think\Template\TagLib;
/**
 * boss自定义标签
 */
class Boss extends TagLib{
    // 标签定义
    protected $tags   =  array(
        // 标签定义： attr 属性列表 close 是否闭合（0 或者1 默认1） alias 标签别名 level 嵌套层次
	    'btoolbar'  => array('attr'=>'options,search','close'=>0),
	    'bcheckbox'  => array('attr'=>'name,checkboxes,checked,separator','close'=>0),
	    'bradio'     => array('attr'=>'name,radios,checked,separator','close'=>0),
	    'bselect'    => array('attr'=>'name,options,values,output,multiple,id,size,first,change,selected,dblclick','close'=>0),
	    'bdatatable' => array('attr'=>'datasource,checkbox,head','close'=>0),
	    'bsearchtable' => array('attr'=>'datasource','close'=>0),
	    'blist'       => array('attr'=>'id,pk,style,action,actionlist,show,datasource,checkbox','close'=>0),
	    'bauth' => array('attr'=>'authname','close'=>1),
    );


	/**
	 * 页面显示权限控制
	 *  eg: <bauth name="Advertiser/edit"><a href="{:U('edit?id='.$item['id'])}">修改</bauth>
	 * @param $tag
	 * @param $content
	 * @return string
	 */
	public function _bauth($tag, $content) {
		$name       = $tag['name']; //权限名称
		if ($name && checkRule($name)) {
			return $content;
		}
		return '';
	}
	

	/**
	 * 内容工具栏
	 * @access public
	 * @param array $options 选项 type id class title
	 * @param array $search 是否显示搜索项
	 * @return string|void
	 */
	public function _btoolbar($tag) {
		$options     = $this->tpl->get($tag['options']);//$this->tpl->get($tag['options']);
		$search     = get_bool($tag['search']);
		$chartBtn     = get_bool($tag['chartbtn']);

		$parseStr   = '<div class="screenOperate fl w">';
		foreach((array)$options as $val) {
			if ($val['type'] == 'a') {
				$target_ = $val["target"] == "_blank"?"target=\"_blank\"":"";
				$parseStr .= "<a class=\"{$val['class']}\" ".$target_."  id=\"{$val['id']}\" href='{$val['url']}'>{$val['title']}</a>";
			} else if ($val['type'] == 'button') {
				$parseStr .= "<input type=\"button\" value=\"{$val['title']}\" class=\"{$val['class']}\" id=\"{$val['id']}\" />";
			}
		}
		if ($search) {
			//$parseStr .= "<input type=\"button\" value=\"高级筛选\" class=\"J_openScreen\" />";
		}
		$parseStr .= "
			<div class=\"dataTableShow fr\" goal=\"dataTable1\">
				<div class=\"dataTableShow_icon fr\"></div>
				<div class=\"dataTable_columnCa pa\">
					<div class=\"dataTable_columnCn\"></div>
					<div class=\"dataTableShow_close tc\">关闭</div>
				</div>
			</div>
			<div class=\"line fr h\"></div>";
		$chartBtn = $chartBtn ? '<div class="statisticsThumbnail_icon J_statisticsThumbnail fr"></div>' : '';
		$parseStr .= $chartBtn . "</div>";

		return $parseStr;
	}


	/**
	 * 搜索栏表格
	 * @param $tag
	 * @param $val =>title选项名称 name选项name value设置的值 options选项值 type页面控件类型
	 * @return string
	 */
	public function _bsearchtable($tag, $content) {
		$datasource  = $this->tpl->get($tag['datasource']);
		$page = !empty($_REQUEST["p"])?trim($_REQUEST["p"]):1;
		//表格body
		$tab_body = '';
		foreach ($datasource as $key=>$val) {

			$key++%3==0 && $tab_body .= '<tr>';
			$tab_body .= "<th>{$val['title']}：</th><td>";
			$checkedValue = isset($_REQUEST[$val['name']]) ? $_REQUEST[$val['name']] : $val['value'];
			switch ($val['type']) {
				case 'text':
					$tab_body .= "<input type='text' name='{$val['name']}' value='{$checkedValue}'/>";
					break;
				case 'datetime':
					break;
				case 'select':
					$_tag['name'] = $val['name'];
					$_tag['class'] = $val['class'];
					//选中值
					$varOp = 'var_checked_'.$_tag['name'];
					$this->tpl->set($varOp, $checkedValue);
					$_tag['selected'] = $varOp;
					//选项列表
					$varOp = 'var_op_'.$_tag['name'];
					$this->tpl->set($varOp, $val['options']);
					$_tag['options'] = $varOp; //变量名
					if($val['title'] == '分配状态'){
					}else{
						$_tag['first'] = '全部';
					}
					$tab_body .= $this->_bselect($_tag);
					break;
				case 'date':
					$format = empty($val['format']) ? 'yyyy-MM-dd HH:mm:ss' : $val['format'];
					$tab_body .= "<input type='text' name='{$val['name']}' value='{$checkedValue}' readonly=\"readonly\" onClick=\"WdatePicker({ dateFmt:'{$format}'})\" class=\"Wdate\"/>";
					break;
			}
			$tab_body .= '</td>';

			if($key == 3 && $key%3==0){
				$tab_body .= '<td><input type="submit" value="查询"/></td></tr>';
			}else{
				$key%3==0 && $tab_body .= '</tr>';
			}

		}

		//按钮
		/*$btnDom = '<tr><td><input type="submit" value="查询"/></td></tr>';

		$tab_body .= $btnDom.'';*/

		$parseStr = '<form id="search_form" method="get" action=""><table class="tableBox">'
			.$tab_body
			.'</table><input type="hidden" name="p" value="'.$page.'"/></form>';
		return $parseStr;
	}


	/**
	 * checkbox标签解析
	 * 格式： <html:checkbox checkboxes="" checked="" />
	 * @access public
	 * @param array $tag 标签属性
	 * @return string|void
	 */
	public function _bcheckbox($tag) {
		$name       = $tag['name'];
		$checkboxes = $tag['checkboxes'];
		$checked    = $tag['checked'];
		$separator  = $tag['separator'] ? $tag['separator'] : '<br />';
		$checkboxes = $this->tpl->get($checkboxes);
		$checked    = $this->tpl->get($checked)?$this->tpl->get($checked):$checked;
		$parseStr   = '';
		foreach($checkboxes as $key=>$val) {
			if($checked == $key  || in_array($key,$checked) ) {
				$parseStr .= '<input type="checkbox" checked="checked" name="'.$name.'[]" value="'.$key.'">'.$val.$separator;
			}else {
				$parseStr .= '<input type="checkbox" name="'.$name.'[]" value="'.$key.'">'.$val.$separator;
			}
		}
		return $parseStr;
	}


	/**
	 * radio标签解析
	 * 格式： <html:radio radios="name" checked="value" />
	 * @access public
	 * @param array $tag 标签属性
	 * @return string|void
	 */
	public function _bradio($tag) {
		$name       = $tag['name'];
		$radios     = $tag['radios'];
		$checked    = $tag['checked'];
		$separator  = $tag['separator'];
		$parseStr   = '';

		if(!empty($radios)) {
			$parseStr   .= '<?php  foreach($'.$radios.' as $key=>$val) { ?>';
			$parseStr   .= '<input type="radio" name="'.$name.'"  value="<?php echo $key; ?>"';
			$parseStr   .= '<?php if(isset($'.$checked.') && ($'.$checked.' == $key)) { ?>';
			$parseStr   .= ' checked="checked"';
			$parseStr   .= '<?php }   ?>';
			$parseStr   .= '<?php echo ">".$val; ?>'.$separator;
			$parseStr   .= '<?php } ?>';
		}

		return $parseStr;
	}


	/**
	 * select标签解析
	 * 格式： <html:select options="name" selected="value" />
	 * @access public
	 * @param array $tag 标签属性
	 * @return string|void
	 */
	public function _bselect($tag) {
		$name       = $tag['name'];
		$options    = $tag['options'];
		$values     = $tag['values'];
		$output     = $tag['output'];
		$multiple   = $tag['multiple'];
		$id         = $tag['id'];
		$size       = $tag['size'];
		$first      = $tag['first'];
		$selected   = $tag['selected'];
		$style      = $tag['style'];
		$class      = $tag['class'];
		$ondblclick = $tag['dblclick'];
		$onchange	= $tag['change'];

		if(!empty($multiple)) {
			$parseStr = '<select id="'.$id.'" name="'.$name.'" ondblclick="'.$ondblclick.'" onchange="'.$onchange.'" multiple="multiple" class="'.$class.'" style="'.$style.'" size="'.$size.'" >';
		}else {
			$parseStr = '<select id="'.$id.'" name="'.$name.'" onchange="'.$onchange.'" ondblclick="'.$ondblclick.'" class="'.$class.'" style="'.$style.'" >';
		}
		if(!empty($first)) {
			$parseStr .= '<option value="" >'.$first.'</option>';
		}
		if(!empty($options)) {
			$parseStr   .= '<?php  foreach($'.$options.' as $key=>$val) { ?>';
			if(!empty($selected)) {
				$parseStr   .= '<?php if($'.$selected.'!="" && ($'.$selected.' == $key )) { ?>';
				$parseStr   .= '<option selected="selected" value="<?php echo $key ?>"><?php echo $val; ?></option>';
				$parseStr   .= '<?php }else { ?><option value="<?php echo $key ?>"><?php echo $val ?></option>';
				$parseStr   .= '<?php } ?>';
			}else {
				$parseStr   .= '<option value="<?php echo $key ?>"><?php echo $val ?></option>';
			}
			$parseStr   .= '<?php } ?>';
		}else if(!empty($values)) {
			$parseStr   .= '<?php  for($i=0;$i<count($'.$values.');$i++) { ?>';
			if(!empty($selected)) {
				$parseStr   .= '<?php if(isset($'.$selected.') && ((is_string($'.$selected.') && $'.$selected.' == $'.$values.'[$i]) || (is_array($'.$selected.') && in_array($'.$values.'[$i],$'.$selected.')))) { ?>';
				$parseStr   .= '<option selected="selected" value="<?php echo $'.$values.'[$i] ?>"><?php echo $'.$output.'[$i] ?></option>';
				$parseStr   .= '<?php }else { ?><option value="<?php echo $'.$values.'[$i] ?>"><?php echo $'.$output.'[$i] ?></option>';
				$parseStr   .= '<?php } ?>';
			}else {
				$parseStr   .= '<option value="<?php echo $'.$values.'[$i] ?>"><?php echo $'.$output.'[$i] ?></option>';
			}
			$parseStr   .= '<?php } ?>';
		}
		$parseStr   .= '</select>';
		return $parseStr;
	}


	/**
	 * 数据表格
	 * @param $tag
	 * @param $contents
	 */
	public function _bdatatable($tag, $content) {
		$datasource = $tag['datasource'];
		$head  = $this->tpl->get($tag['header']);
		$checkbox    = get_bool($tag['checkbox']);
		$datakey     = $tag['datakey'];

		$cbDom = '<input  type="checkbox">';
		//表格head
		$tab_head = '<thead><tr>';
		if ($checkbox) {
			$tab_head .= "<th width='40'>$cbDom</th>";
		}
		foreach ($head as $key=>$val) {
			$tab_head .= '<th>'.$val.'</th>';
		}

		$tab_head .= '</tr></thead>';
		//表格body
		$tab_body = '<tbody><volist name="'.$datasource.'" id="'.$datakey.'" >';
			$tab_body .= '<tr>';
			if ($checkbox) {
				$tab_body .= "<td>{$cbDom}</td>";
			}
			foreach ($val as $field) {
				$tab_body .= "<td>{$field}</td>";
			}
			$tab_body .= '</tr>';
		$tab_body .= '</volist></tbody>';

		$parseStr = '<table class="dataTable dataTable1">'.$tab_head.$tab_body.'</table>';
		return $parseStr;
	}


	/**
	 * list标签解析
	 * 格式： <html:list datasource="" show="" />
	 * @access public
	 * @param array $tag 标签属性
	 * @return string
	 */
	public function _list($tag) {
		$id         = $tag['id'];                       //表格ID
		$datasource = $tag['datasource'];               //列表显示的数据源VoList名称
		$pk         = empty($tag['pk'])?'id':$tag['pk'];//主键名，默认为id
		$style      = $tag['style'];                    //样式名
		$name       = !empty($tag['name'])?$tag['name']:'vo';                 //Vo对象名
		$action     = $tag['action']=='true'?true:false;                   //是否显示功能操作
		$key         =  !empty($tag['key'])?true:false;
		$sort      = $tag['sort']=='false'?false:true;
		$checkbox   = $tag['checkbox'];                 //是否显示Checkbox
		if(isset($tag['actionlist'])) {
			if(substr($tag['actionlist'],0,1)=='$') {
				$actionlist   = $this->tpl->get(substr($tag['actionlist'],1));
			}else {
				$actionlist   = $tag['actionlist'];
			}
			$actionlist = explode(',',trim($actionlist));    //指定功能列表
		}

		if(substr($tag['show'],0,1)=='$') {
			$show   = $this->tpl->get(substr($tag['show'],1));
		}else {
			$show   = $tag['show'];
		}
		$show       = explode(',',$show);                //列表显示字段列表

		//计算表格的列数
		$colNum     = count($show);
		if(!empty($checkbox))   $colNum++;
		if(!empty($action))     $colNum++;
		if(!empty($key))  $colNum++;

		//显示开始
		$parseStr	= "<!-- Think 系统列表组件开始 -->\n";
		$parseStr  .= '<table id="'.$id.'" class="tableBox" cellpadding=0 cellspacing=0 >';
		$parseStr  .= '<tr><td height="5" colspan="'.$colNum.'" class="topTd" ></td></tr>';
		$parseStr  .= '<tr class="row" >';
		//列表需要显示的字段
		$fields = array();
		foreach($show as $val) {
			$fields[] = explode(':',$val);
		}
		if(!empty($checkbox) && 'true'==strtolower($checkbox)) {//如果指定需要显示checkbox列
			$parseStr .='<th width="8"><input type="checkbox" id="check" onclick="CheckAll(\''.$id.'\')"></th>';
		}
		if(!empty($key)) {
			$parseStr .= '<th width="12">No</th>';
		}
		foreach($fields as $field) {//显示指定的字段
			$property = explode('|',$field[0]);
			$showname = explode('|',$field[1]);
			if(isset($showname[1])) {
				$parseStr .= '<th width="'.$showname[1].'">';
			}else {
				$parseStr .= '<th>';
			}
			$showname[2] = isset($showname[2])?$showname[2]:$showname[0];
			if($sort) {
				$parseStr .= '<a href="javascript:sortBy(\''.$property[0].'\',\'{$sort}\',\''.ACTION_NAME.'\')" title="按照'.$showname[2].'{$sortType} ">'.$showname[0].'<eq name="order" value="'.$property[0].'" ><img src="__PUBLIC__/images/{$sortImg}.gif" width="12" height="17" border="0" align="absmiddle"></eq></a></th>';
			}else{
				$parseStr .= $showname[0].'</th>';
			}

		}
		if(!empty($action)) {//如果指定显示操作功能列
			$parseStr .= '<th >操作</th>';
		}

		$parseStr .= '</tr>';
		$parseStr .= '<volist name="'.$datasource.'" id="'.$name.'" ><tr class="row" ';	//支持鼠标移动单元行颜色变化 具体方法在js中定义
		if(!empty($checkbox)) {
			//    $parseStr .= 'onmouseover="over(event)" onmouseout="out(event)" onclick="change(event)" ';
		}
		$parseStr .= '>';
		if(!empty($checkbox)) {//如果需要显示checkbox 则在每行开头显示checkbox
			$parseStr .= '<td><input type="checkbox" name="key"	value="{$'.$name.'.'.$pk.'}"></td>';
		}
		if(!empty($key)) {
			$parseStr .= '<td>{$i}</td>';
		}
		foreach($fields as $field) {
			//显示定义的列表字段
			$parseStr   .=  '<td>';
			if(!empty($field[2])) {
				// 支持列表字段链接功能 具体方法由JS函数实现
				$href = explode('|',$field[2]);
				if(count($href)>1) {
					//指定链接传的字段值
					// 支持多个字段传递
					$array = explode('^',$href[1]);
					if(count($array)>1) {
						foreach ($array as $a){
							$temp[] =  '\'{$'.$name.'.'.$a.'|addslashes}\'';
						}
						$parseStr .= '<a href="javascript:'.$href[0].'('.implode(',',$temp).')">';
					}else{
						$parseStr .= '<a href="javascript:'.$href[0].'(\'{$'.$name.'.'.$href[1].'|addslashes}\')">';
					}
				}else {
					//如果没有指定默认传编号值
					$parseStr .= '<a href="javascript:'.$field[2].'(\'{$'.$name.'.'.$pk.'|addslashes}\')">';
				}
			}
			if(strpos($field[0],'^')) {
				$property = explode('^',$field[0]);
				foreach ($property as $p){
					$unit = explode('|',$p);
					if(count($unit)>1) {
						$parseStr .= '{$'.$name.'.'.$unit[0].'|'.$unit[1].'} ';
					}else {
						$parseStr .= '{$'.$name.'.'.$p.'} ';
					}
				}
			}else{
				$property = explode('|',$field[0]);
				if(count($property)>1) {
					$parseStr .= '{$'.$name.'.'.$property[0].'|'.$property[1].'}';
				}else {
					$parseStr .= '{$'.$name.'.'.$field[0].'}';
				}
			}
			if(!empty($field[2])) {
				$parseStr .= '</a>';
			}
			$parseStr .= '</td>';

		}
		if(!empty($action)) {//显示功能操作
			if(!empty($actionlist[0])) {//显示指定的功能项
				$parseStr .= '<td>';
				foreach($actionlist as $val) {
					if(strpos($val,':')) {
						$a = explode(':',$val);
						if(count($a)>2) {
							$parseStr .= '<a href="javascript:'.$a[0].'(\'{$'.$name.'.'.$a[2].'}\')">'.$a[1].'</a>&nbsp;';
						}else {
							$parseStr .= '<a href="javascript:'.$a[0].'(\'{$'.$name.'.'.$pk.'}\')">'.$a[1].'</a>&nbsp;';
						}
					}else{
						$array	=	explode('|',$val);
						if(count($array)>2) {
							$parseStr	.= ' <a href="javascript:'.$array[1].'(\'{$'.$name.'.'.$array[0].'}\')">'.$array[2].'</a>&nbsp;';
						}else{
							$parseStr .= ' {$'.$name.'.'.$val.'}&nbsp;';
						}
					}
				}
				$parseStr .= '</td>';
			}
		}
		$parseStr	.= '</tr></volist><tr><td height="5" colspan="'.$colNum.'" class="bottomTd"></td></tr></table>';
		$parseStr	.= "\n<!-- Think 系统列表组件结束 -->\n";
		return $parseStr;
	}

}