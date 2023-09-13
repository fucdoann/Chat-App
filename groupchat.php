<?php 

//privatechat.php

session_start();

if(!isset($_SESSION['user_data']))
{
	header('location:index.php');
}

require('database/ChatUser.php');
require('database/GroupChat.php');
require('database/ChatRooms.php');
?>

<!DOCTYPE html>
<html>

<head>
    <title>HustChat</title>
    <!-- Bootstrap core CSS -->
    <link href="vendor-front/bootstrap/bootstrap.min.css" rel="stylesheet">

    <link href="vendor-front/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">

    <link rel="stylesheet" type="text/css" href="vendor-front/parsley/parsley.css" />

    <!-- Bootstrap core JavaScript -->
    <script src="vendor-front/jquery/jquery.min.js"></script>
    <script src="vendor-front/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor-front/jquery-easing/jquery.easing.min.js"></script>

    <script type="text/javascript" src="vendor-front/parsley/dist/parsley.min.js"></script>
    <style type="text/css">
    html,
    body {
        height: 100%;
        width: 100%;
        margin: 0;
    }

    #wrapper {
        display: flex;
        flex-flow: column;
        height: 100%;
    }

    #remaining {
        flex-grow: 1;
    }

    #messages {
        height: 200px;
        background: whitesmoke;
        overflow: auto;
    }

    #chat-room-frm {
        margin-top: 10px;
    }

    #user_list {
        height: 450px;
        overflow-y: auto;
    }

    #messages_area {
        height: 75vh;
        overflow-y: auto;
        /*background-color:#e6e6e6;*/
        /*background-color: #EDE6DE;*/
    }
    </style>
</head>

<body>
    <div class="container-fluid">

        <div class="row">

            <div class="col-lg-3 col-md-4 col-sm-5"
                style="background-color: #f1f1f1; height: 100vh; border-right:1px solid #ccc;">
                <?php
				
				$login_user_id = '';

				$token = '';

				foreach($_SESSION['user_data'] as $key => $value)
				{
					$login_user_id = $value['id'];

					$token = $value['token'];

				?>
                <input type="hidden" name="login_user_id" id="login_user_id" value="<?php echo $login_user_id; ?>" />

                <input type="hidden" name="is_active_chat" id="is_active_chat" value="No" />

                <div class="mt-3 mb-3 text-center">
                    <img src="<?php echo $value['profile']; ?>" class="img-fluid rounded-circle img-thumbnail"
                        width="150" />
                    <h3 class="mt-2"><?php echo $value['name']; ?></h3>
                    <a href="profile.php" class="btn btn-secondary mt-2 mb-2">Edit</a>
                    <input type="button" class="btn btn-success mt-2 mb-2" id="createGroup" name="createGroup"
                        value="CreateGroup" />
                    <input type="button" class="btn btn-primary mt-2 mb-2" id="logout" name="logout" value="Logout" />
                    <div>
                        <input type="button" class="btn btn-info" id="addMember" name="addMember" value="Add" />
                        <input type="button" class="btn btn-danger" id="deleteMember" value="Delete" />
                    </div>
                    <input type="text" class="form-control col-lg-7 mx-auto" id="groupName" placeholder="Name of group">

                </div>
                <?php
				}

				$group_object = new groupChat;

				$group_object->setHostUserId($login_user_id);

				$group_data = $group_object->getAllGroupChat();
				?>
                <div class="list-group"
                    style=" max-height: 100vh; margin-bottom: 10px; overflow-y:scroll; -webkit-overflow-scrolling: touch;">
                    <?php
					
					foreach($group_data as $key => $group)
					{
							$icon = '<i class="fa fa-circle text-success"></i>';
                            $group_id = $group['group_id'];
							$group_object->setGroupId($group_id);
                        	$group_object->setGroupChatNameById();


                            

							echo "
							<a class='list-group-item list-group-item-action select_user' style='cursor:pointer' data-groupid = '".$group['group_id']."'>
								<span class='ml-1'>
									<strong>
										<span id='list_group_name_".$group["group_id"]."'>".$group_object->getGroupName()."</span>
										<input type='hidden' id='host_user_id' value='".$group_object->getHostUserIdByGroupId()	."'/>
									</strong>
								</span>
								<span class='mt-2 float-right' id='userstatus_".$group['group_id']."'>".$icon."</span>
							</a>
							";
						
					}


					?>
                </div>
                <div class="card mt-3 member-list">
                    <div class="card-header">List User</div>
                    <div class="card-body">
                        <div class="list-group user-list"
                            style=" max-height: 30vh; margin-bottom: 10px; overflow-y:scroll; -webkit-overflow-scrolling: touch;">

                        </div>
                    </div>

                </div>
            </div>

            <div class="col-lg-9 col-md-8 col-sm-5">
                <br />
                <h3 class="text-center">HustChat - Join your Group</h3>
                <hr />
                <br />
                <div id="chat_area"></div>
            </div>

        </div>

    </div>
</body>
<script type="text/javascript">
$('#groupName').hide();
$('#addMember').hide();
$('#deleteMember').hide();
$(".member-list").hide();
$(document).ready(function() {

    var receiver_groupid = '';

    var conn = new WebSocket('ws://localhost:8080?token=<?php echo $token; ?>');

    conn.onopen = function(event) {
        console.log('Connection Established');
    };

    conn.onmessage = function(event) {
        var data = JSON.parse(event.data);

        if (data.status_type == 'Online') {
            $('#userstatus_' + data.user_id_status).html('<i class="fa fa-circle text-success"></i>');
        } else if (data.status_type == 'Offline') {
            $('#userstatus_' + data.user_id_status).html('<i class="fa fa-circle text-danger"></i>');
        } else {

            var row_class = '';
            var background_class = '';

            if (data.from == 'Me') {
                row_class = 'row justify-content-start';
                background_class = 'alert-primary';
            } else {
                row_class = 'row justify-content-end';
                background_class = 'alert-success';
            }

            if (receiver_userid == data.userId || data.from == 'Me') {
                if ($('#is_active_chat').val() == 'Yes') {
                    var html_data = `
						<div class="` + row_class + `">
							<div class="col-sm-10">
								<div class="shadow-sm alert ` + background_class + `">
									<b>` + data.from + ` - </b>` + data.msg + `<br />
									<div class="text-right">
										<small><i>` + data.datetime + `</i></small>
									</div>
								</div>
							</div>
						</div>
						`;

                    $('#messages_area').append(html_data);

                    $('#messages_area').scrollTop($('#messages_area')[0].scrollHeight);

                    $('#chat_message').val("");
                }
            } else {
                var count_chat = $('#userid' + data.userId).text();

                if (count_chat == '') {
                    count_chat = 0;
                }

                count_chat++;

                $('#userid_' + data.userId).html('<span class="badge badge-danger badge-pill">' +
                    count_chat + '</span>');
            }
        }
    };

    conn.onclose = function(event) {
        console.log('connection close');
    };

    function make_chat_area(group_name) {
        var html = `
			<div class="card">
				<div class="card-header">
					<div class="row">
						<div class="col col-sm-6">
							<b><span class="text-danger" id="chat_user_name">` + group_name + `</span></b>
						</div>
						<div class="col col-sm-6 text-right">
							<a href="chatroom.php" class="btn btn-success btn-sm">Group Chat</a>&nbsp;&nbsp;&nbsp;
							<button type="button" class="close" id="close_chat_area" data-dismiss="alert" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
					</div>
				</div>
				<div class="card-body" id="messages_area">

				</div>
			</div>

			<form id="chat_form" method="POST" data-parsley-errors-container="#validation_error">
				<div class="input-group mb-3" style="height:7vh">
					<textarea class="form-control" id="chat_message" name="chat_message" placeholder="Type Message Here" data-parsley-maxlength="1000" data-parsley-pattern="/^[a-zA-Z0-9 ]+$/" required></textarea>
					<div class="input-group-append">
						<button type="submit" name="send" id="send" class="btn btn-primary"><i class="fa fa-paper-plane"></i></button>
					</div>
				</div>
				<div id="validation_error"></div>
				<br />
			</form>
			`;

        $('#chat_area').html(html);

        $('#chat_form').parsley();
    }

    $(document).on('click', '.select_user', function() {
        $(".member-list").hide();
        $(".user-list").html('');
        receiver_groupid = $(this).data('groupid');
        receiver_group_name = $('#list_group_name_' + receiver_groupid).text();

        var from_user_id = $('#login_user_id').val();
        var host_user_id = $('#host_user_id').val();
        if (from_user_id == host_user_id) {
            $('#addMember').show();
            $('#deleteMember').show();

        }

        $('.select_user.active').removeClass('active');

        $(this).addClass('active');

        make_chat_area(receiver_group_name);

        $('#is_active_chat').val('Yes');

        $.ajax({
            url: "action.php",
            method: "POST",
            data: {
                action: 'fetch_group',
                to_group_id: receiver_groupid,
                from_user_id: from_user_id
            },
            dataType: "JSON",
            success: function(data) {
                if (data.length > 0) {
                    var html_data = '';

                    for (var count = 0; count < data.length; count++) {
                        var row_class = '';
                        var background_class = '';
                        var user_name = '';
                        if (data[count].from_user_id == from_user_id) {

                            row_class = 'row justify-content-start';

                            background_class = 'alert-primary';

                            user_name = 'Me';
                        } else {
                            row_class = 'row justify-content-end';

                            background_class = 'alert-success';

                            user_name = data[count].from_user_name;
                        }

                        html_data += `
							<div class="` + row_class + `">
								<div class="col-sm-10">
									<div class="shadow alert ` + background_class + `">
										<b>` + user_name + ` - </b>
										` + data[count].chat_message + `<br />
										<div class="text-right">
											<small><i>` + data[count].timestamp + `</i></small>
										</div>
									</div>
								</div>
							</div>
							`;
                    }


                    $('#messages_area').html(html_data);

                    $('#messages_area').scrollTop($('#messages_area')[0].scrollHeight);
                }
            }
        });
        $('#addMember').click(function() {
            var group_id = receiver_groupid;
            $(".member-list").hide();
            $(".user-list").html('');
            $.ajax({
                url: "action.php",
                method: "POST",
                data: {
                    action: 'add_member',
                    group_id: group_id
                },
                dataType: "JSON",
                success: function(data) {
                    console.log("hello");
                    if (data.length > 0) {
                        var html_data = '';
                        for (var count = 0; count < data.length; count++) {
                            var user_name = data[count].user_name;
                            var user_id = data[count].user_id;
                            var user_profile = data[count].user_profile;
                            html_data += `
							<a class="list-group-item">
                                <img src="` + user_profile + `" class="img-fluid rounded-circle img-thumbnail" width="50" alt=""/>
								<span class="ml-1">
									<strong>` + user_name + `</strong>
								</span>
								<span class="mt-2 float-right">
									<i class="fa fa-plus" aria-hidden="true" data-userid="` + user_id + `"></i>
								</span>
							</a>
							`;


                        }
                        $(".member-list").show();
                        $(".user-list").html(html_data);
                        $(document).ready(function(){
                            $(".fa-plus").click(function(){
                                var add_user_id = $(this).data('userid');
                                console.log(group_id);
                                $.ajax({
                                    url:"action.php",
                                    type:"POST",
                                    data:{
                                        action: 'add_user',
                                        group_id:group_id,
                                        add_user_id:add_user_id
                                    },
                                    dataType:"JSON",
                                    success:function(data){
                                        
                                    }
                                })
                            })
                        })
                    }
                }
            })
        });
        $('#deleteMember').click(function() {
            var group_id = receiver_groupid;
            $(".member-list").hide();
            $(".user-list").html('');
            $.ajax({
                url: "action.php",
                method: "POST",
                data: {
                    action: 'delete_member',
                    group_id: group_id
                },
                dataType: "JSON",
                success: function(data) {
                    var html_data = '';
                    if (data.length > 0) {
                        for (var count = 0; count < data.length; count++) {
                            user_name = data[count].user_name;
                            user_id = data[count].user_id;
                            var user_profile = data[count].user_profile;
                            html_data += `
							<a class="list-group-item">
                                <img src="` + user_profile + `" class="img-fluid rounded-circle img-thumbnail" width="50" alt=""/>
								<span class="ml-1">
									<strong>` + user_name + `</strong>
								</span>
								<span class="mt-2 float-right">
									<i class="fa fa-trash text-danger" data-userid="` + user_id + `"></i>
								</span>
							</a>
							`;


                        }
                        $(".member-list").show();
                        $(".user-list").html(html_data);
                    }
                }
            })
        })

    });

    $(document).on('click', '#close_chat_area', function() {

        $('#chat_area').html('');

        $('.select_user.active').removeClass('active');

        $('#is_active_chat').val('No');

        receiver_userid = '';

    });

    $(document).on('submit', '#chat_form', function(event) {

        event.preventDefault();

        if ($('#chat_form').parsley().isValid()) {
            var user_id = parseInt($('#login_user_id').val());

            var message = $('#chat_message').val();

            var data = {
                userId: user_id,
                msg: message,
                receiver_groupid: receiver_groupid,
                command: 'group'
            };

            conn.send(JSON.stringify(data));
        }

    });

    $('#logout').click(function() {

        user_id = $('#login_user_id').val();

        $.ajax({
            url: "action.php",
            method: "POST",
            data: {
                user_id: user_id,
                action: 'leave'
            },
            success: function(data) {
                var response = JSON.parse(data);
                if (response.status == 1) {
                    conn.close();

                    location = 'index.php';
                }
            }
        })

    });
	$('.fa').click(function(){
		var user_id = $(this).data
	})
    $('#createGroup').click(function() {
        $('#groupName').show();
    });
    $('#groupName').keyup(function(e) {
        if (e.keyCode == 13) {
            var groupName = $('#groupName').val();
            host_user_id = $('#login_user_id').val();
            $.ajax({
                url: "action.php",
                method: "POST",
                data: {
                    host_user_id: host_user_id,
                    group_name: groupName,
                    action: 'createGroup'
                },
                success: function(data) {
                    $('#groupName').val("");
                    $('#groupName').hide();
                }
            })
        }
    })

})
</script>

</html>