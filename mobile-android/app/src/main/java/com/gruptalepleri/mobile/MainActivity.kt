package com.gruptalepleri.mobile

import android.Manifest
import android.app.DownloadManager
import android.content.ActivityNotFoundException
import android.content.Intent
import android.content.pm.PackageManager
import android.net.Uri
import android.os.Build
import android.os.Bundle
import android.os.Environment
import android.provider.MediaStore
import android.webkit.CookieManager
import android.webkit.JavascriptInterface
import android.webkit.ValueCallback
import android.webkit.WebChromeClient
import android.webkit.WebResourceRequest
import android.webkit.WebSettings
import android.webkit.WebView
import android.webkit.WebViewClient
import android.widget.Toast
import androidx.activity.OnBackPressedCallback
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import androidx.core.content.FileProvider
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout
import com.google.android.material.appbar.MaterialToolbar
import com.google.android.material.progressindicator.LinearProgressIndicator
import com.google.firebase.messaging.FirebaseMessaging
import java.io.File
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

class MainActivity : AppCompatActivity() {

    private lateinit var webView: WebView
    private lateinit var swipeRefresh: SwipeRefreshLayout
    private lateinit var progressBar: LinearProgressIndicator

    private var filePathCallback: ValueCallback<Array<Uri>>? = null
    private var fileChooserParams: WebChromeClient.FileChooserParams? = null
    private var cameraImageUri: Uri? = null

    private var currentPushToken: String? = null

    private val fileChooserLauncher =
        registerForActivityResult(ActivityResultContracts.StartActivityForResult()) { result ->
            val callback = filePathCallback
            if (callback == null) {
                clearFileChooserState()
                return@registerForActivityResult
            }

            val parsed = WebChromeClient.FileChooserParams.parseResult(result.resultCode, result.data)
            val uris = when {
                parsed != null && parsed.isNotEmpty() -> parsed
                result.resultCode == RESULT_OK && cameraImageUri != null -> arrayOf(cameraImageUri!!)
                else -> null
            }
            callback.onReceiveValue(uris)
            clearFileChooserState()
        }

    private val cameraPermissionLauncher =
        registerForActivityResult(ActivityResultContracts.RequestPermission()) { granted ->
            launchChooserWithCamera(granted)
        }

    private val notificationPermissionLauncher =
        registerForActivityResult(ActivityResultContracts.RequestPermission()) { _ -> }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)

        val toolbar = findViewById<MaterialToolbar>(R.id.toolbar)
        webView = findViewById(R.id.webView)
        swipeRefresh = findViewById(R.id.swipeRefresh)
        progressBar = findViewById(R.id.progressBar)

        setSupportActionBar(toolbar)
        toolbar.setNavigationOnClickListener {
            if (webView.canGoBack()) webView.goBack() else finish()
        }
        toolbar.setOnMenuItemClickListener { item ->
            when (item.itemId) {
                R.id.action_refresh -> {
                    webView.reload()
                    true
                }

                R.id.action_open_browser -> {
                    openExternal(webView.url ?: BuildConfig.WEB_BASE_URL)
                    true
                }

                else -> false
            }
        }

        configureWebView()
        configureRefresh()
        requestNotificationPermissionIfNeeded()
        initializePush()

        val startUrl = intent?.getStringExtra(EXTRA_OPEN_URL)?.takeIf { it.isNotBlank() }
            ?: BuildConfig.WEB_BASE_URL
        webView.loadUrl(startUrl)

        onBackPressedDispatcher.addCallback(this, object : OnBackPressedCallback(true) {
            override fun handleOnBackPressed() {
                if (webView.canGoBack()) {
                    webView.goBack()
                } else {
                    finish()
                }
            }
        })
    }

    override fun onNewIntent(intent: Intent) {
        super.onNewIntent(intent)
        setIntent(intent)
        val target = intent.getStringExtra(EXTRA_OPEN_URL)?.takeIf { it.isNotBlank() } ?: return
        webView.loadUrl(target)
    }

    override fun onDestroy() {
        clearFileChooserState()
        webView.apply {
            stopLoading()
            webChromeClient = null
            webViewClient = null
            removeJavascriptInterface(JS_BRIDGE_NAME)
            destroy()
        }
        super.onDestroy()
    }

    private fun configureRefresh() {
        swipeRefresh.setOnRefreshListener { webView.reload() }
    }

    private fun configureWebView() {
        webView.settings.apply {
            javaScriptEnabled = true
            domStorageEnabled = true
            allowFileAccess = true
            allowContentAccess = true
            databaseEnabled = true
            loadsImagesAutomatically = true
            mixedContentMode = WebSettings.MIXED_CONTENT_COMPATIBILITY_MODE
            mediaPlaybackRequiresUserGesture = false
            builtInZoomControls = false
            displayZoomControls = false
        }

        CookieManager.getInstance().apply {
            setAcceptCookie(true)
            setAcceptThirdPartyCookies(webView, true)
        }

        webView.addJavascriptInterface(MobileBridge(), JS_BRIDGE_NAME)

        webView.webViewClient = object : WebViewClient() {
            override fun shouldOverrideUrlLoading(view: WebView?, request: WebResourceRequest?): Boolean {
                val uri = request?.url ?: return false
                return handleUri(uri)
            }

            override fun onPageFinished(view: WebView?, url: String?) {
                super.onPageFinished(view, url)
                swipeRefresh.isRefreshing = false
                publishPushTokenToWeb()
            }
        }

        webView.webChromeClient = object : WebChromeClient() {
            override fun onProgressChanged(view: WebView?, newProgress: Int) {
                super.onProgressChanged(view, newProgress)
                progressBar.progress = newProgress
                progressBar.visibility = if (newProgress in 1..99) android.view.View.VISIBLE else android.view.View.GONE
            }

            override fun onShowFileChooser(
                webView: WebView?,
                filePathCallback: ValueCallback<Array<Uri>>?,
                fileChooserParams: FileChooserParams?
            ): Boolean {
                this@MainActivity.filePathCallback?.onReceiveValue(null)
                this@MainActivity.filePathCallback = filePathCallback
                this@MainActivity.fileChooserParams = fileChooserParams

                val hasCameraPermission = ContextCompat.checkSelfPermission(
                    this@MainActivity,
                    Manifest.permission.CAMERA
                ) == PackageManager.PERMISSION_GRANTED

                if (hasCameraPermission) {
                    launchChooserWithCamera(true)
                } else {
                    cameraPermissionLauncher.launch(Manifest.permission.CAMERA)
                }
                return true
            }
        }

        webView.setDownloadListener { url, userAgent, contentDisposition, mimeType, _ ->
            try {
                val request = DownloadManager.Request(Uri.parse(url))
                request.setMimeType(mimeType)
                request.addRequestHeader("User-Agent", userAgent)
                request.addRequestHeader("cookie", CookieManager.getInstance().getCookie(url))
                request.setDescription("Dosya indiriliyor...")
                request.setTitle(contentDisposition.ifBlank { "gruptalepleri_dosya" })
                request.setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED)
                request.setDestinationInExternalPublicDir(Environment.DIRECTORY_DOWNLOADS, contentDisposition)

                val dm = getSystemService(DOWNLOAD_SERVICE) as DownloadManager
                dm.enqueue(request)
                Toast.makeText(this, "Indirme basladi", Toast.LENGTH_SHORT).show()
            } catch (e: Throwable) {
                Toast.makeText(this, "Indirme baslatilamadi", Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun launchChooserWithCamera(includeCamera: Boolean) {
        val callback = filePathCallback
        if (callback == null) return

        val baseIntent = Intent(Intent.ACTION_OPEN_DOCUMENT).apply {
            addCategory(Intent.CATEGORY_OPENABLE)
            type = "*/*"
            putExtra(Intent.EXTRA_ALLOW_MULTIPLE, fileChooserParams?.mode == WebChromeClient.FileChooserParams.MODE_OPEN_MULTIPLE)

            val accepts = fileChooserParams?.acceptTypes
                ?.flatMap { it.split(",") }
                ?.map { it.trim() }
                ?.filter { it.isNotEmpty() && it != "*/*" }
                ?.distinct()
                ?.toTypedArray()
            if (!accepts.isNullOrEmpty()) {
                putExtra(Intent.EXTRA_MIME_TYPES, accepts)
            }
        }

        val initialIntents = mutableListOf<Intent>()
        if (includeCamera) {
            createCameraIntent()?.let(initialIntents::add)
        }

        val chooser = Intent(Intent.ACTION_CHOOSER).apply {
            putExtra(Intent.EXTRA_INTENT, baseIntent)
            putExtra(Intent.EXTRA_INITIAL_INTENTS, initialIntents.toTypedArray())
        }

        try {
            fileChooserLauncher.launch(chooser)
        } catch (e: ActivityNotFoundException) {
            callback.onReceiveValue(null)
            clearFileChooserState()
        }
    }

    private fun createCameraIntent(): Intent? {
        return try {
            val photoFile = createTempImageFile()
            val authority = "${BuildConfig.APPLICATION_ID}.fileprovider"
            cameraImageUri = FileProvider.getUriForFile(this, authority, photoFile)

            Intent(MediaStore.ACTION_IMAGE_CAPTURE).apply {
                putExtra(MediaStore.EXTRA_OUTPUT, cameraImageUri)
                addFlags(Intent.FLAG_GRANT_WRITE_URI_PERMISSION or Intent.FLAG_GRANT_READ_URI_PERMISSION)
            }
        } catch (_: Throwable) {
            cameraImageUri = null
            null
        }
    }

    private fun createTempImageFile(): File {
        val timeStamp = SimpleDateFormat("yyyyMMdd_HHmmss", Locale.US).format(Date())
        val imageFileName = "GTP_${timeStamp}_"
        return File.createTempFile(imageFileName, ".jpg", cacheDir)
    }

    private fun handleUri(uri: Uri): Boolean {
        val scheme = uri.scheme?.lowercase(Locale.ROOT) ?: return false
        if (scheme in setOf("tel", "mailto", "smsto", "geo", "intent")) {
            openExternal(uri.toString())
            return true
        }

        if (scheme == "http" || scheme == "https") {
            val host = uri.host?.lowercase(Locale.ROOT).orEmpty()
            val trustedHosts = setOf(
                "gruptalepleri.com",
                "www.gruptalepleri.com",
                "gruptalepleri.test",
                "10.0.2.2",
                "localhost"
            )
            return if (trustedHosts.contains(host) || host.endsWith(".gruptalepleri.com")) {
                false
            } else {
                openExternal(uri.toString())
                true
            }
        }
        return false
    }

    private fun openExternal(url: String) {
        try {
            startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(url)))
        } catch (_: Throwable) {
            Toast.makeText(this, "Link acilamadi", Toast.LENGTH_SHORT).show()
        }
    }

    private fun requestNotificationPermissionIfNeeded() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            val granted = ContextCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS) ==
                PackageManager.PERMISSION_GRANTED
            if (!granted) {
                notificationPermissionLauncher.launch(Manifest.permission.POST_NOTIFICATIONS)
            }
        }
    }

    private fun initializePush() {
        try {
            FirebaseMessaging.getInstance().token.addOnCompleteListener { task ->
                if (!task.isSuccessful) return@addOnCompleteListener
                val token = task.result ?: return@addOnCompleteListener
                getSharedPreferences(PREFS_NAME, MODE_PRIVATE).edit().putString(KEY_PUSH_TOKEN, token).apply()
                currentPushToken = token
                publishPushTokenToWeb()
            }
        } catch (_: Throwable) {
            // Push config optional for first setup.
        }
    }

    private fun publishPushTokenToWeb() {
        val token = currentPushToken ?: getSharedPreferences(PREFS_NAME, MODE_PRIVATE).getString(KEY_PUSH_TOKEN, null)
        val escaped = token?.replace("\\", "\\\\")?.replace("'", "\\'") ?: ""
        webView.evaluateJavascript(
            "window.dispatchEvent(new CustomEvent('gtp:pushToken', { detail: { token: '$escaped' } }));",
            null
        )
    }

    private fun clearFileChooserState() {
        filePathCallback = null
        fileChooserParams = null
        cameraImageUri = null
    }

    private inner class MobileBridge {
        @JavascriptInterface
        fun getPlatform(): String = "android"

        @JavascriptInterface
        fun getPushToken(): String {
            val token = currentPushToken ?: getSharedPreferences(PREFS_NAME, MODE_PRIVATE).getString(KEY_PUSH_TOKEN, "")
            return token.orEmpty()
        }
    }

    companion object {
        const val EXTRA_OPEN_URL = "open_url"
        private const val JS_BRIDGE_NAME = "GTPMobile"
        private const val PREFS_NAME = "gtp_mobile_prefs"
        private const val KEY_PUSH_TOKEN = "push_token"
    }
}
