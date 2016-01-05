import com.google.gson.JsonArray;
import com.google.gson.JsonElement;
import com.google.gson.JsonObject;

import java.lang.reflect.Array;
import java.lang.reflect.Constructor;
import java.lang.reflect.InvocationTargetException;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 * Created by wangtao on 15/12/21.
 */
public class HaoResult {

    public int errorCode = 0;
    public String errorStr;
    public JsonElement extraInfo;
    public JsonElement results;
    public String modelType;
    public String searchIndexString;

    public Map<String, Object> pathCache;

    public static Object instanceModel(JsonElement results, int errorCode, String errorStr, JsonElement extraInfo) {

        String modelType = "HaoResult";

        if (results != null && results instanceof JsonObject) {
            if (((JsonObject) results).get("modelType") != null) {
                modelType = ((JsonObject) results).get("modelType").getAsString();
            }
        }

        String resultName = HaoResult.class.getPackage().getName() + ".HaoResult";

        if (!modelType.equals("HaoResult")) {
            resultName = modelType + "Result";
            resultName = HaoResult.class.getPackage().getName() + ".results." + resultName;
        }

        Class c;
        HaoResult objc = null;
        try {
            c = Class.forName(resultName);
            Constructor constructor = c.getDeclaredConstructor();
            constructor.setAccessible(true);

            objc = (HaoResult) constructor.newInstance();
        } catch (ClassNotFoundException e) {
            e.printStackTrace();
            objc = new HaoResult();
        } catch (InvocationTargetException e) {
            e.printStackTrace();
        } catch (NoSuchMethodException e) {
            e.printStackTrace();
        } catch (InstantiationException e) {
            e.printStackTrace();
        } catch (IllegalAccessException e) {
            e.printStackTrace();
        }

        objc.errorCode = errorCode;
        objc.errorStr = errorStr;
        objc.extraInfo = extraInfo;
        objc.results = results;
        objc.modelType = modelType;

        objc.pathCache = new HashMap<>();

        return objc;
    }

    public Object find(String path) {

        path = path.trim();

        if (pathCache.containsKey(path)) {
            return pathCache.get(path);
        } else {
            if (!path.startsWith("results>") && !path.startsWith("extraInfo>")) {
                path = "results>" + path;
            }

            String[] paths = path.split(">");

            JsonElement changeValue = null;

            for (int i = 0; i < paths.length; i++) {
                if (i == 0) {
                    if (paths[0].equals("extraInfo")) {
                        changeValue = this.extraInfo;
                    } else {
                        changeValue = this.results;
                    }
                } else {
                    String keyItem = paths[i];
                    if (keyItem.length() > 0) {
                        if (keyItem.matches("\\d+")) {
                            if (changeValue instanceof JsonArray) {
                                if (Integer.parseInt(keyItem) <= ((JsonArray) changeValue).size()) {
                                    changeValue = ((JsonArray) changeValue).getAsJsonArray().get(Integer.parseInt(keyItem));
                                    continue;
                                }
                            }
                        } else {
                            if (changeValue instanceof JsonObject) {
                                changeValue = ((JsonObject) changeValue).get(keyItem);
                                continue;
                            }
                        }
                        changeValue = null;
                        break;
                    }
                }
            }

            Object value = value(changeValue);
            pathCache.put(path, value);
            return value;
        }

    }

    public Object value(JsonElement value) {
        if (value instanceof JsonObject) {
            if (((JsonObject) value).get("modelType") != null) {
                return HaoResult.instanceModel(value, this.errorCode, this.errorStr, this.extraInfo);
            }
        } else if (value instanceof JsonArray) {
            ArrayList<Object> array = new ArrayList<>();
            for (int i = 0; i < ((JsonArray) value).size(); i++) {
                JsonElement objc = (JsonElement) ((JsonArray) value).get(i);
                array.add(value(objc));
            }
            return array;
        }
        return value;
    }

    /**
     * @param path
     * @return
     */
    public ArrayList<Object> findAsList(String path) {
        Object objc = find(path);
        if (!(objc instanceof JsonArray || objc instanceof List || objc instanceof Array)) {
            ArrayList<Object> list = new ArrayList<>();
            list.add(objc);
            return list;
        } else {
            return (ArrayList<Object>) objc;
        }
    }

    public String findAsString(String path) {
        try {
            return find(path).toString();
        } catch (Exception e) {
        }
        return "";
    }

    public int findAsInt(String path) {
        try {
            return Integer.parseInt(find(path).toString());
        } catch (Exception e) {

        }
        return -1;
    }

    public Object findAsResult(String path) {
        Object objc = find(path);
        if (!(objc instanceof HaoResult)) {
            if (objc instanceof JsonElement) {
                return instanceModel((JsonElement) objc, this.errorCode, this.errorStr, this.extraInfo);
            }
        }
        return objc;
    }

    public ArrayList<Object> search(String path) {
        if (this.searchIndexString == null) {
            JsonObject resultObjc = new JsonObject();
            resultObjc.add("results", this.results);
            ArrayList<String> resultsIndex = HaoUtility.getKeyIndexArray(resultObjc);

            JsonObject extraInfoObjc = new JsonObject();
            extraInfoObjc.add("extraInfo", this.extraInfo);
            ArrayList<String> extraIndex = HaoUtility.getKeyIndexArray(extraInfoObjc);
            resultsIndex.addAll(extraIndex);

            ArrayList<String> searchIndex = resultsIndex;

            StringBuffer tempIndex = new StringBuffer();
            for (int i = 0; i < searchIndex.size(); i++) {
                tempIndex.append(searchIndex.get(i) + "\n");
            }
            HaoUtility.print(tempIndex.toString());
            this.searchIndexString = tempIndex.toString();
        }

        path = path.trim();

        if (!path.startsWith("results>") && !path.startsWith("extraInfo>")) {
            path = "results>" + path;
        }

        ArrayList<Object> result = new ArrayList<>();

        String regEx = "(^|\\s)(" + path + ")\\s+";
        Pattern pat = Pattern.compile(regEx);
        Matcher mat = pat.matcher(this.searchIndexString);
        while (mat.find()) {
            result.add(find(mat.group()));
        }

        return result;
    }

    public boolean isModelType(String modelType) {
        return this.modelType.equalsIgnoreCase(modelType);
    }

    public boolean isErrorCode(int errorCode) {
        return this.errorCode == errorCode;
    }

    public boolean isResultsOK() {
        return isErrorCode(0);
    }
}
