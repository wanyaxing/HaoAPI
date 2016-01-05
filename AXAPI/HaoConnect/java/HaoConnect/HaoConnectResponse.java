
/**
 * Created by wangtao on 15/12/21.
 */
public interface HaoConnectResponse {

    void requestOnSuccess(HaoResult result);

    void requestOnStart();

    void requestOnFail(HaoResult results);

}
