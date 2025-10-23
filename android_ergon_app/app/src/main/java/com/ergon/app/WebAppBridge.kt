package com.ergon.app

import android.annotation.SuppressLint
import android.app.Activity
import android.content.SharedPreferences
import android.webkit.JavascriptInterface
import android.webkit.WebView
import org.json.JSONArray
import org.json.JSONObject
import java.util.*

class WebAppBridge(
    private val activity: Activity,
    private val webView: WebView,
    private val prefs: SharedPreferences
) {

    @JavascriptInterface
    fun saveToken(token: String) {
        prefs.edit().putString("jwt_token", token).apply()
    }

    @JavascriptInterface
    fun clearToken() {
        prefs.edit().remove("jwt_token").apply()
    }

    @JavascriptInterface
    fun getLocation() {
        LocationHelper.getLastLocation(activity) { lat, lng ->
            val js = "window.__ergon_onLocation && window.__ergon_onLocation($lat, $lng);"
            activity.runOnUiThread { 
                webView.evaluateJavascript(js, null) 
            }
        }
    }

    @JavascriptInterface
    fun queueAttendance(json: String) {
        try {
            val currentQueue = prefs.getString("attendance_queue", "[]")
            val arr = JSONArray(currentQueue)
            val attendanceData = JSONObject(json)
            attendanceData.put("client_uuid", UUID.randomUUID().toString())
            attendanceData.put("timestamp", System.currentTimeMillis())
            arr.put(attendanceData)
            prefs.edit().putString("attendance_queue", arr.toString()).apply()
        } catch (e: Exception) {
            e.printStackTrace()
        }
    }

    @JavascriptInterface
    fun queueTaskUpdate(json: String) {
        try {
            val currentQueue = prefs.getString("task_queue", "[]")
            val arr = JSONArray(currentQueue)
            val taskData = JSONObject(json)
            taskData.put("client_uuid", UUID.randomUUID().toString())
            taskData.put("timestamp", System.currentTimeMillis())
            arr.put(taskData)
            prefs.edit().putString("task_queue", arr.toString()).apply()
        } catch (e: Exception) {
            e.printStackTrace()
        }
    }

    @JavascriptInterface
    fun syncQueue() {
        activity.runOnUiThread { 
            webView.evaluateJavascript("window.__ergon_syncQueue && window.__ergon_syncQueue();", null) 
        }
    }

    @JavascriptInterface
    fun getQueuedData(type: String): String {
        return when (type) {
            "attendance" -> prefs.getString("attendance_queue", "[]") ?: "[]"
            "tasks" -> prefs.getString("task_queue", "[]") ?: "[]"
            else -> "[]"
        }
    }

    @JavascriptInterface
    fun clearQueue(type: String) {
        when (type) {
            "attendance" -> prefs.edit().putString("attendance_queue", "[]").apply()
            "tasks" -> prefs.edit().putString("task_queue", "[]").apply()
        }
    }

    @JavascriptInterface
    fun registerFCMToken(token: String) {
        prefs.edit().putString("fcm_token", token).apply()
        // Notify web app to register token with server
        activity.runOnUiThread {
            webView.evaluateJavascript(
                "window.__ergon_registerFCM && window.__ergon_registerFCM('$token');", null
            )
        }
    }

    @JavascriptInterface
    fun showToast(message: String) {
        activity.runOnUiThread {
            android.widget.Toast.makeText(activity, message, android.widget.Toast.LENGTH_SHORT).show()
        }
    }
}