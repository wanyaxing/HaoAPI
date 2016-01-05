import android.text.TextUtils;
import android.util.Log;

import com.google.gson.JsonArray;
import com.google.gson.JsonElement;
import com.google.gson.JsonObject;

import java.io.File;
import java.io.FileInputStream;
import java.math.BigInteger;
import java.net.URL;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.util.ArrayList;
import java.util.Iterator;
import java.util.Map;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 * Created by wangtao on 15/12/21.
 */
public class HaoUtility {

    public static String getFileMD5(File file) {
        if (!file.isFile()) {
            return null;
        }
        MessageDigest digest = null;
        FileInputStream in = null;
        byte buffer[] = new byte[1024];
        int len;
        try {
            digest = MessageDigest.getInstance("MD5");
            in = new FileInputStream(file);
            while ((len = in.read(buffer, 0, 1024)) != -1) {
                digest.update(buffer, 0, len);
            }
            in.close();
        } catch (Exception e) {
            e.printStackTrace();
            return null;
        }
        BigInteger bigInt = new BigInteger(1, digest.digest());
        return bigInt.toString(16);
    }

    /**
     * 用MD5算法进行加密
     *
     * @param str 需要加密的字符串
     * @return MD5加密后的结果
     */
    public static String encodeMD5String(String str) {
        return encode(str, "MD5");
    }

    /**
     * 用SHA算法进行加密
     *
     * @param str 需要加密的字符串
     * @return SHA加密后的结果
     */
    public static String encodeSHAString(String str) {
        return encode(str, "SHA");
    }

    private static String encode(String str, String method) {
        MessageDigest md = null;
        String dstr = null;
        try {
            md = MessageDigest.getInstance(method);
            md.update(str.getBytes());
            dstr = new BigInteger(1, md.digest()).toString(16);
            if (dstr.length() < 32) {
                for (int i = 0; i < 32 - dstr.length(); i++) {
                    dstr = "0" + dstr;
                }
            }
        } catch (NoSuchAlgorithmException e) {
            e.printStackTrace();
        }
        return dstr;
    }

    /**
     * @param string
     * @return 返回 切割Http之后的链接部分
     */
    public static String httpStringFilter(String string) {
        Pattern pattern = Pattern
                .compile("^http.*?://(.*?)(/*[?#].*$|[?#].*$|/*$)");
        Matcher matcher = pattern.matcher(string);
        return matcher.replaceAll("$1").trim();
    }

    public enum JSON_TYPE{
        /**JSONObject*/
        JSON_TYPE_OBJECT,
        /**JSONArray*/
        JSON_TYPE_ARRAY,
        /**不是JSON格式的字符串*/
        JSON_TYPE_ERROR
    }
    /***
     *
     * 获取JSON类型
     *         判断规则
     *             判断第一个字母是否为{或[ 如果都不是则不是一个JSON格式的文本
     *
     * @param str
     * @return
     */
    public static JSON_TYPE getJSONType(String str){
        if(TextUtils.isEmpty(str)){
            return JSON_TYPE.JSON_TYPE_ERROR;
        }

        final char[] strChar = str.substring(0, 1).toCharArray();
        final char firstChar = strChar[0];

        if(firstChar == '{'){
            return JSON_TYPE.JSON_TYPE_OBJECT;
        }else if(firstChar == '['){
            return JSON_TYPE.JSON_TYPE_ARRAY;
        }else{
            return JSON_TYPE.JSON_TYPE_ERROR;
        }
    }

    public static void print(String str)
    {
        Log.d("TTLog", str);
    }

    /**
     * 取得当前类所在的文件
     * @param clazz
     * @return
     */
    public static File getClassFile(Class clazz){
        URL path = clazz.getResource(clazz.getName().substring(clazz.getName().lastIndexOf(".")+1)+".classs");
        if(path == null){
            String name = clazz.getName().replaceAll("[.]", "/");
            path = clazz.getResource("/"+name+".class");
        }
        return new File(path.getFile());
    }
    /**
     * 得到当前类的路径
     * @param clazz
     * @return
     */
    public static String getClassFilePath(){
        try{
            return java.net.URLDecoder.decode(getClassFile(HaoUtility.class).getAbsolutePath(),"UTF-8");
        }catch (Exception e) {
            // TODO: handle exception
            e.printStackTrace();
            HaoUtility.print(e + "");
            return "";
        }
    }

    /**
     * 取得当前类所在的ClassPath目录，比如tomcat下的classes路径
     * @param clazz
     * @return
     */
    public static File getClassPathFile(Class clazz){
        File file = getClassFile(clazz);
        for(int i=0,count = clazz.getName().split("[.]").length; i<count; i++)
            file = file.getParentFile();
        if(file.getName().toUpperCase().endsWith(".JAR!")){
            file = file.getParentFile();
        }
        return file;
    }
    /**
     * 取得当前类所在的ClassPath路径
     * @param clazz
     * @return
     */
    public static String getClassPath(Class clazz){
        try{
            return java.net.URLDecoder.decode(getClassPathFile(clazz).getAbsolutePath(),"UTF-8");
        }catch (Exception e) {
            // TODO: handle exception
            e.printStackTrace();
            return "";
        }
    }




    public static ArrayList<String> getKeyIndexArray(Object target)
    {
        ArrayList<String> keyList = new ArrayList<>();
        if (target instanceof JsonArray) {
            for (int key = 0; key < ((JsonArray) target).size(); key++)
            {
                keyList.add(key + "");
                Object objc = ((JsonArray) target).get(key);
                if (objc instanceof JsonObject || objc instanceof JsonArray)
                {
                    ArrayList<String> keyListTemp = getKeyIndexArray(objc);
                    for (String keyTemp:
                         keyListTemp) {
                        keyList.add(key + ">" + keyTemp);
                    }
                }

            }
        } else {
            if (target instanceof JsonObject) {

                Iterator var2 = ((JsonObject) target).entrySet().iterator();

                while (var2.hasNext()) {
                    Map.Entry entry = (Map.Entry) var2.next();
                    String key = (String) entry.getKey();
                    keyList.add(key + "");
                    JsonElement objc = (JsonElement) entry.getValue();
                    if (objc instanceof JsonObject || objc instanceof JsonArray) {
                        ArrayList<String> keyListTemp = getKeyIndexArray(objc);
                        for (String keyTemp :
                                keyListTemp) {
                            keyList.add(key + ">" + keyTemp);
                        }
                    }
                }
            }
        }
        return keyList;
    }

    public static String replace(String string) {
        Pattern pattern = Pattern
                .compile(".*?");
        Matcher matcher = pattern.matcher(string);
        return matcher.replaceAll("$1").trim();
    }

}
