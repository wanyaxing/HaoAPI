import android.content.Context;

import com.google.gson.Gson;
import com.google.gson.JsonObject;
import com.loopj.android.http.AsyncHttpResponseHandler;
import com.loopj.android.http.RequestParams;

import java.util.ArrayList;
import java.util.Collections;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import cz.msebera.android.httpclient.Header;

/**
 * Created by wangtao on 15/12/21.
 */
public class HaoConnect {

    private static String Clientinfo = "";
    private static String Clientversion = "0.1";
    private static String SECRET_HAX_CONNECT = "";
    private static String ApiHost = "";

    private static String Devicetype = "3";
    private static String Devicetoken = "";
    private static String Requesttime = "0";

    private static String Userid = "";
    private static String Logintime = "";
    private static String Checkcode = "";

    public static String METHOD_GET = "get";
    public static String METHOD_POST = "post";

    /**
     * 是否开启调试模式
     */
    private static String Isdebug = "0";

    /**
     * 请求加密后的校验串，服务器会使用同样规则加密请求后，比较校验串是否一致，从而防止请求内容被纂改。
     * 取头信息里Clientversion,Devicetype,Requesttime,Devicetoken,Userid,Logintime,Checkcode,Clientinfo,Isdebug  和 表单数据
     * 每个都使用key=value（空则空字符串）格式组合成字符串然后放入同一个数组
     * 再放入请求地址（去除http://和末尾/?#之后的字符）后
     * 并放入私钥字符串后自然排序
     * 连接为字符串后进行MD5加密，获得Signature
     * 将Signature也放入头信息，进行传输。
     */
    private static String Signature = "";

    /**
     * 使用前必须调用该初始化方法
     */
    public static void init() {
        if (Clientinfo == null || Clientinfo.length() == 0) {
            Clientinfo = HaoConfig.HAOCONNECT_CLIENTINFO;
            SECRET_HAX_CONNECT = HaoConfig.HAOCONNECT_SECRET_HAX;
            ApiHost = HaoConfig.HAOCONNECT_APIHOST;
        }
    }

    /**
     * 设置版本号
     */
    public void setClientVersion(String clientVersion) {
        Clientversion = clientVersion;
    }

    /**
     * 设置用户相关信息
     * @param userID        用户id
     * @param loginTime     登录时间
     * @param checkCode
     */
    public static void setCurrentUserInfo(String userID, String loginTime, String checkCode) {
        Userid = userID;
        Logintime = loginTime;
        Checkcode = checkCode;

        HaoConfig.putString("userID", Userid);
        HaoConfig.putString("loginTime", Logintime);
        HaoConfig.putString("checkCode", Checkcode);
    }

    /**
     * 推送token
     * @param deviceToken
     */
    public static void setCurrentDeviceToken(String deviceToken) {
        Devicetoken = deviceToken;
    }

    /**
     * @param requestData
     * @param urlParam
     * @return 请求头数据，里面包括加密字段
     */
    private static Map<String, Object> getSecretHeaders(Map<String, Object> requestData, String urlParam) {
        Map<String, Object> headers = new HashMap<>();
        headers.put("Clientinfo", Clientinfo);
        headers.put("Clientversion", Clientversion);
        headers.put("Devicetype", Devicetype);
        headers.put("Requesttime", (System.currentTimeMillis() / 1000) + "");
        headers.put("Devicetoken", Devicetoken);
        headers.put("Isdebug", "0");

        if (Userid == null || Userid.equals(""))
        {
            Userid = HaoConfig.getString("userID");
            Logintime = HaoConfig.getString("loginTime");
            Checkcode = HaoConfig.getString("checkCode");
        }
        headers.put("Userid", Userid);
        headers.put("Logintime", Logintime);
        headers.put("Checkcode", Checkcode);

        Map<String, Object> signMap = new HashMap<>();
        signMap.putAll(headers);
        signMap.putAll(requestData);
        Map<String, Object> linkMap = new HashMap<String, Object>();
        linkMap.put("link", HaoUtility.httpStringFilter("http://" + ApiHost + "/" + urlParam));
        signMap.putAll(linkMap);
        headers.put("Signature", getSignature(signMap));
        return headers;
    }

    /**
     * 加密算法
     */
    private static String getSignature(Map<String, Object> map) {
        List<String> tmpArr = new ArrayList<String>();
        String secret = "";
        for (Map.Entry<String, Object> entry : map.entrySet()) {
            String data = entry.getKey() + "=" + entry.getValue();
            tmpArr.add(data);
        }
        tmpArr.add(SECRET_HAX_CONNECT);
        Collections.sort(tmpArr);
        for (String string : tmpArr) {
            secret += string;
        }
        return HaoUtility.encodeMD5String(secret);
    }

    public static void loadContent(String urlParam, Map<String, Object> params, String method, final HaoRequestResopnse haoRequestResopnse, Context context) {
        RequestParams requestParams = new RequestParams();
        for (Map.Entry<String, Object> entry : params.entrySet()) {
            requestParams.put(entry.getKey(), entry.getValue() + "");
        }
        HaoHttpClient.loadContent("http://" + ApiHost + "/" + urlParam, requestParams, method, getSecretHeaders(params, urlParam), new AsyncHttpResponseHandler() {
            @Override
            public void onSuccess(int i, Header[] headers, byte[] bytes) {
                String response = new String(bytes);
                haoRequestResopnse.requestOnSuccess(response);
            }

            @Override
            public void onFailure(int i, Header[] headers, byte[] bytes, Throwable throwable) {
                String response = new String(bytes);
                haoRequestResopnse.requestOnSuccess(response);
            }

            @Override
            public void onStart() {
                super.onStart();
                haoRequestResopnse.requestOnStart();
            }
        }, context);
    }

    public static void loadJson(String urlParam, Map<String, Object> params, String method, final HaoRequestResopnse haoRequestResopnse, Context context) {
        loadContent(urlParam, params, method, new HaoRequestResopnse() {
            @Override
            public void requestOnSuccess(Object result) {
                try {
                    Gson gson = new Gson();
                    JsonObject jsonObject = gson.fromJson(result.toString(), JsonObject.class);
                    haoRequestResopnse.requestOnSuccess(jsonObject);
                } catch (Exception e) {
                    haoRequestResopnse.requestOnFail("json解析失败，请检查返回结果");
                }
            }

            @Override
            public void requestOnStart() {
                haoRequestResopnse.requestOnStart();
            }

            @Override
            public void requestOnFail(Object results) {
                haoRequestResopnse.requestOnFail(results);
            }
        }, context);
    }

    public static void request(String urlParam, Map<String, Object> params, String method, final HaoConnectResponse haoConnectResponse, Context context) {

        loadJson(urlParam, params, method, new HaoRequestResopnse() {
            @Override
            public void requestOnSuccess(Object result) {
                try {
                    JsonObject jsonObject = (JsonObject) result;
                    HaoResult haoResult = (HaoResult) HaoResult.instanceModel(jsonObject.get("results"), jsonObject.get("errorCode").getAsInt(), jsonObject.get("errorStr").getAsString(), jsonObject.get("extraInfo"));

                    if (haoResult.isResultsOK()) {
                        haoConnectResponse.requestOnSuccess(haoResult);
                    } else {
                        haoConnectResponse.requestOnFail(haoResult);
                    }
                } catch (Exception e) {
                    HaoResult haoResult = (HaoResult) HaoResult.instanceModel(null, -1, e.toString(), null);
                    haoConnectResponse.requestOnFail(haoResult);
                }
            }

            @Override
            public void requestOnStart() {
                haoConnectResponse.requestOnStart();
            }

            @Override
            public void requestOnFail(Object results) {

                HaoResult haoResult = (HaoResult) HaoResult.instanceModel(null, -1, results.toString(), null);
                haoConnectResponse.requestOnFail(haoResult);
            }
        }, context);
    }

    public static void cancelRequest(Context context) {
        HaoHttpClient.cancelRequest(context);
    }

    public interface HaoRequestResopnse
    {
        void requestOnSuccess(Object result);

        void requestOnStart();

        void requestOnFail(Object results);
    }
}
