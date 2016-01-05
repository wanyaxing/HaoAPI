HaoConnect 安卓使用说明

1.导入所需要的第三方库；
android-async-http.jar;
gson.jar;
httpclient.jar;

2.将HaoConnect文件夹集成到工程中，直接拖入即可；

3.开发时，需要按照HaoConfig_example中的格式进行具体的工程配置；

4.创建具体的请求类，需要继承HaoConnect类；

5.创建具体的结果类，需要继承HaoResult类；

6.所需权限：

	<uses-permission android:name="android.permission.INTERNET" />
    <uses-permission android:name="android.permission.ACCESS_NETWORK_STATE" />
    <uses-permission android:name="android.permission.ACCESS_WIFI_STATE" />
    <uses-permission android:name="android.permission.READ_PHONE_STATE" />
    <uses-permission android:name="android.permission.WRITE_EXTERNAL_STORAGE" />
    <uses-permission android:name="android.permission.RESTART_PACKAGES" />
    <uses-permission android:name="android.permission.CAMERA" />
    <uses-permission android:name="android.permission.FLASHLIGHT" />
    <uses-permission android:name="android.permission.VIBRATE" />
    <uses-permission android:name="android.permission.MOUNT_UNMOUNT_FILESYSTEMS" />
    <uses-permission android:name="android.permission.RECORD_AUDIO" />
    <uses-permission android:name="android.permission.GET_ACCOUNTS" />
    <uses-permission android:name="android.permission.USE_CREDENTIALS" />
    <uses-permission android:name="android.permission.MANAGE_ACCOUNTS" />
    <uses-permission android:name="android.permission.AUTHENTICATE_ACCOUNTS" />
    <uses-permission android:name="com.android.launcher.permission.READ_SETTINGS" />
    <uses-permission android:name="android.permission.CHANGE_WIFI_STATE" />
    <uses-permission android:name="android.permission.BROADCAST_STICKY" />
    <uses-permission android:name="android.permission.WRITE_SETTINGS" />
    <uses-permission android:name="android.permission.CALL_PHONE" />

7.一些具体的开发相关操作：
    a.初始化
        HaoConnect.init();
    b.如果需要保存用户信息
        HaoConnect.setCurrentUserInfo();
    c.传入DeviceToken
        HaoConnect.setCurrentDeviceToken();
    d.传入版本号
        aoConnect.setClientVersion();