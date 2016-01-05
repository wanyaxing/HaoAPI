import android.content.Context;

import com.loopj.android.http.AsyncHttpClient;
import com.loopj.android.http.AsyncHttpResponseHandler;
import com.loopj.android.http.JsonHttpResponseHandler;
import com.loopj.android.http.RequestParams;

import java.util.Locale;
import java.util.Map;

/**
 * Created by wangtao on 15/11/25.
 */
public class HaoHttpClient {

    private static AsyncHttpClient client = new AsyncHttpClient();

    /**
     * @param actionUrl 请求地址
     * @param params    请求参数
     * @param Method    请求类型
     * @param headers   请求头
     * @param response  回调方法
     */
    public static void loadContent(String actionUrl, RequestParams params, String Method, Map<String, Object> headers, AsyncHttpResponseHandler response) {
        for (Map.Entry<String, Object> header : headers.entrySet()) {
            client.addHeader(header.getKey(), header.getValue() + "");
        }
        client.addHeader("Accept-Language", Locale.getDefault().toString());
        client.addHeader("Connection", "Keep-Alive");

        if (Method == null || Method.equals("get")) {
            client.get(actionUrl, params, response);
        } else {
            client.post(actionUrl, params, response);
        }
    }

    public static void loadContent(String actionUrl, RequestParams params, String Method, Map<String, Object> headers, AsyncHttpResponseHandler response, Context context) {

        for (Map.Entry<String, Object> header : headers.entrySet()) {
            client.addHeader(header.getKey(), header.getValue() + "");
        }
        client.addHeader("Accept-Language", Locale.getDefault().toString());
        client.addHeader("Connection", "Keep-Alive");

        if (Method == null || Method.equals("get")) {
            client.get(context, actionUrl, params, response);
        } else {
            client.post(context, actionUrl, params, response);
        }
    }

    public static void loadJson(String actionUrl, RequestParams params, String Method, Map<String, Object> headers, JsonHttpResponseHandler response) {
        for (Map.Entry<String, Object> header : headers.entrySet()) {
            client.addHeader(header.getKey(), header.getValue() + "");
        }
        client.addHeader("Accept-Language", Locale.getDefault().toString());
        client.addHeader("Connection", "Keep-Alive");

        if (Method == null || Method.equals("get")) {
            client.get(actionUrl, params, response);
        } else {
            client.post(actionUrl, params, response);
        }
    }

    public static void loadJson(String actionUrl, RequestParams params, String Method, Map<String, Object> headers, JsonHttpResponseHandler response, Context context) {
        for (Map.Entry<String, Object> header : headers.entrySet()) {
            client.addHeader(header.getKey(), header.getValue() + "");
        }
        client.addHeader("Accept-Language", Locale.getDefault().toString());
        client.addHeader("Connection", "Keep-Alive");

        if (Method == null || Method.equals("get")) {
            client.get(context, actionUrl, params, response);
        } else {
            client.post(context, actionUrl, params, response);
        }
    }

    /**
     * 取消请求
     *
     * @param context
     */
    public static void cancelRequest(Context context) {
        HaoUtility.print("取消请求:" + context);
        client.cancelRequests(context, true);
    }
}
