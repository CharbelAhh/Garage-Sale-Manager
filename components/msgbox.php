<div class="msg-box <?php if (!empty($result)) echo "active"; ?>">
    <div class="box-title">
        <h1><?php if ($result=="success") echo "Success"; else echo "Fail"; ?></h1>
        <i class="fa fa-<?php if ($result=="success") echo "check"; else echo "x"; ?>"></i>
    </div>
    <p class="box-msg"><?php echo $msg; ?></p>
    <button class="close-box">Ok</button>
</div>