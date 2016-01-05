import android.content.SharedPreferences;
import android.util.Log;


/**
 * Created by wangtao on 15/12/21.
 */
public class HaoConfig_example {

    public static String HAOCONNECT_CLIENTINFO      = "???";
    public static String HAOCONNECT_CLIENTVERSION   = "1.0";
    public static String HAOCONNECT_SECRET_HAX      = "secret=???";
    public static String HAOCONNECT_APIHOST         = "???.???.com";

    public static void putString(String key, String value)
    {
        try {
            //AppContext 这里是Demo里面的Application子类，开发时候需要替换成自己相关的类
            SharedPreferences sharedPreferences = AppContext.getInstance().getSharedPreferences("config",
                    0);
            SharedPreferences.Editor editor = sharedPreferences.edit();
            editor.putString(key, value);
            editor.commit();
        }catch (Exception e){
            Log.e("putStringInfo", e.getMessage());
        }
    }

    public static String getString(String key)
    {
        try {
            SharedPreferences sharedPreferences = AppContext.getInstance().getSharedPreferences("config",
                    0);
            return sharedPreferences.getString(key, null);
        }catch (Exception e){
            return "";
        }
    }
}
