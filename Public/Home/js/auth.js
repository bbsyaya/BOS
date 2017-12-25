var auth={

    pageType: "",
    init:function(){
        this.initVal();
        this.pageSwitch();
    },
    initVal:function(){
        auth.type_id  = $("#type_id").val();
        auth.datatype = $("#datatype").val();
    },
    type_id:0,// type_id 1=用户，2=角色，3=部门
    datatype:0,// datatype 1=功能权限，2=数据权限
    pageSwitch: function() {
        if ("undefined" != typeof pType) {
            auth.pageType = pType
        }
        auth._publiFun();
        auth.pageComFun();
        switch (auth.pageType) {
            case "Auth_authList":
                auth.initLoad();
                // auth.initUserDataAuth();//加载数据权限
            break;
        }
    },
    _publiFun:function(){

    },
    pageComFun:function(){

    },
    initLoad:function(){
        //用户功能权限功能
        switch(parseInt(auth.datatype)){
            case 1:
            //功能权限
               auth.initAuth();//加载功能
            break;
            
            case 2:
            //数据权限
                auth.initUserDataAuth();//加载数据权限
            break;
        }
        

    },
    setAuth:function(userid,type_id,datatype,type_str){
        layer.open({
          type: 2,
          title: type_str+'功能权限',
          shadeClose: true,
          shade: 0.8,
          area: ['400px', '80%'],
          content:"/Home/Auth/authList.html?userid="+userid+"&type_id="+type_id+"&datatype="+datatype
        }); 
    },
     setDataAuth:function(userid,type_id,datatype,type_str){
        layer.open({
          type: 2,
          title: type_str+'数据权限',
          shadeClose: true,
          shade: 0.8,
          area: ['500px', '500px'],
          content:"/Home/Auth/authList.html?userid="+userid+"&type_id="+type_id+"&datatype="+datatype
        }); 
    },
    setRuleGrant_fun:function(userid){
        layer.open({
          type: 2,
          title: '设置可授权权限',
          shadeClose: true,
          shade: 0.8,
          area: ['500px', '500px'],
          content:"/Home/Auth/authList.html?userid="+userid+"&type_id=4&datatype=1"
        }); 
    },
    setRuleGrant_data:function(userid){
        layer.open({
          type: 2,
          title: '设置可授权权限',
          shadeClose: true,
          shade: 0.8,
          area: ['500px', '500px'],
          content:"/Home/Auth/authList.html?userid="+userid+"&type_id=5&datatype=2"
        }); 
    },
    grantRule_fun:function(userid){
        layer.open({
          type: 2,
          title: '功能授权',
          shadeClose: true,
          shade: 0.8,
          area: ['500px', '500px'],
          content:"/Home/Auth/authList.html?userid="+userid+"&type_id=6&datatype=1"
        }); 
    },
    grantRule_data:function(userid){
        layer.open({
          type: 2,
          title: '数据授权',
          shadeClose: true,
          shade: 0.8,
          area: ['500px', '500px'],
          content:"/Home/Auth/authList.html?userid="+userid+"&type_id=7&datatype=2"
        }); 
    },
    getruleh:function(){
      //显示已授且生效中的权限
      var params      = {};
      params.paramsid = $("#userid").val();
        $.get("/Home/Auth/getruleh.html",params,function(data){       
            if(auth.type_id==6){
              $('.type1').hide();
            }else{
              $('.type2').hide();
            }
            var html='<style>td{text-align:center;border-bottom:1px solid #000;}</style><table style="width:100%;">';
            html+='<tr><td style="width:135px;">到期时间</td><td>权限列表</td><td style="width:85px;">是否临时权限</td></tr>';
            for(o in data){
              isls=(data[o].type==1)?'否':'是';
              html+='<tr><td>'+data[o]['endtime']+'</td><td>'+data[o].allrule+'</td><td>'+isls+'</td></tr>';
            }
            html+='</table>';
            html+='<p style="text-align:center;"><input type="button" value="返回" class="mits-btn" onclick="auth.tograntrule()"/></p>';
            $('.type3').html(html).show();
        },'json');
    },
    tograntrule:function(){
      if(auth.type_id==6){
        $('.type1').show();
      }else{
        $('.type2').show();
      }
      $('.type3').html('').hide();
    },
    initAuth:function(){
        //加载功能权限
        var setting = {
                check: {
                    enable: true
                },
                data: {
                    simpleData: {
                        enable: true,
                        idKey: "id",
                        pIdKey: "pid",
                        rootPId: 0
                    }
                }
            };
        var params      = {};
        params.type_id  = auth.type_id;
        params.paramsid = $("#userid").val();
        $.get("/Home/Auth/getAuthList.html",params,function(zNodes){       
            treeObj = $.fn.zTree.init($("#userGNTree"), setting, zNodes);
            treeObj.expandAll(true);
            var ids = auth.datatype+"_"+auth.type_id;
            $("#auth_"+ids).show();
        });
    },
    ////加载用户数据权限
    initUserDataAuth:function(){
         var params={};
         params.type_id=auth.type_id;
         params.paramsid=$("#userid").val();
        $.get("/Home/Auth/getDataAuthList.html",params,function(data){       
            if(data.userlist.length>0){
                var h="";
                $.each(data.userlist,function(i,o){
                    h+='<option value="'+o.id+'" '+o.selected+'>'+o.real_name+'</option>'; 
                });
                $("#all_user").html(h);
                $("#all_user").selectpicker({
                     selectAllText: '全选',
                    deselectAllText: '全不选'
                });
            }

            if(data.linelist.length>0){
                var h="";
                $.each(data.linelist,function(i,o){
                    h+='<option value="'+o.id+'" '+o.selected+' >'+o.name+'</option>';
                });
                $("#all_line").html(h);
                $("#all_line").selectpicker({
                    selectAllText: '全选',
                    deselectAllText: '全不选'
                });
            }

            var ids = auth.datatype+"_"+auth.type_id;
            $("#auth_"+ids).show();

        });
    },
    //设置功能权限，保存
    getUserGNAuth:function(type_id_){
        var chkNodes = treeObj.getCheckedNodes();
        var chkIds_true = [],chkIds_delay=[],ckno=0;
        if (chkNodes) {
            for(var obj in chkNodes) {
                //选中
                chkIds_true.push(chkNodes[obj].id);
                ckno++;
            }
        }
        var params={};
        if(ckno>0){
            params.chkIds_true =chkIds_true;
            params.userId      = $("#userid").val();
            params.type_id_gn    = type_id_;
            if(type_id_==6){
                params.chongfu   = $("input[name='chongfu']:checked").val();
                var htime='';
                $('input[name="htime"]:checked').each(function(){
                    htime+=$(this).val()+',';
                })
                params.htime=htime;
                params.is_worktime=$('input[name="is_worktime"]').is(":checked");
                params.linshi=$('input[name="linshi"]').val();
            }
            var ids            = auth.datatype+"_"+auth.type_id;
            $("#auth_"+ids).attr("onclick","").val("保存中...");
            $.post("/Home/Auth/saveGNAuth.html",params,function(data){
                if(data.code==200){
                    layer.msg("配置成功");
                }else{
                    layer.msg("配置失败请联系管理员");
                }
                window.parent.location.reload();
            });
        }else{
            layer.msg("请选择权限菜单");
        }
    },
   
    setRoleDataAuth:function(userid){
        layer.open({
          type: 2,
          title: '角色功能权限分配',
          shadeClose: true,
          shade: 0.8,
          area: ['800px', '50%'],
          content:"/Home/Auth/authList.html?userid="+userid+"&type_id=2&datatype=2"
        }); 
    },

    //设置用户，角色，部门数据权限
    saveUserDataAuth:function(type_id_){
        var params      ={};
        params.all_line = $("#all_line").val();
        params.all_user = $("#all_user").val();
        params.userid   = $("#userid").val();
        if(type_id_==7){
            params.chongfu   = $("input[name='chongfu']:checked").val();
            var htime='';
            $('input[name="htime"]:checked').each(function(){
                htime+=$(this).val()+',';
            })
            params.htime=htime;
            params.is_worktime=$('input[name="is_worktime"]').is(":checked");
            params.linshi=$('input[name="linshi"]').val();
        }
        if(params.all_line==null && params.all_user==null){
            layer.confirm("您没有分配数据权限,确定保存？",function(){
                auth.saveData_(params,type_id_);
            });
        }else{
            auth.saveData_(params,type_id_);
        }
        
    },
    saveData_:function(params,type_id_){
        var ids = auth.datatype+"_"+auth.type_id;
        $("#auth_"+ids).attr("onclick","").val("保存中...");
        params.type_id_data = type_id_;
        $.post("/Home/Auth/saveDataAuth.html",params,function(data){
            if(data.code==200){
                layer.msg("配置成功");
                window.parent.location.reload();
            }else{
                layer.msg("配置失败请联系管理员");
            }
        });
    }
};

$(function(){auth.init();});
