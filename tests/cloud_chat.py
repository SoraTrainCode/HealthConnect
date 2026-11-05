"""Guest chatbot integration checks with local AI mocks; no database or real key.

Creates a temporary source copy and never reads the application's config.local.php.
Run from the repository root: python tests/cloud_chat.py
"""

import base64
import contextlib
import json
import os
from pathlib import Path
import re
import shutil
import socket
import subprocess
import tempfile
import threading
import time
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

import requests


ROOT = Path(__file__).resolve().parents[1]
DISCLAIMER = (
    "\n\nThông tin chỉ mang tính tham khảo, vui lòng đến cơ sở y tế "
    "để được chẩn đoán chính xác."
)
TEST_KEY = "cloud-chat-test-only-key"
QUESTION = "Tôi nên ngủ bao nhiêu giờ mỗi ngày?"
MISSING_KEY_ERROR = "Chưa cấu hình ai_api_key cho chatbot trong config.local.php."
checks = 0


def check(value, label):
    global checks
    if not value:
        raise AssertionError(label)
    checks += 1
    print("PASS CLOUD:", label)


class MockAI(BaseHTTPRequestHandler):
    def do_POST(self):
        self.server.calls.append({
            "path": self.path,
            "authorization": self.headers.get("Authorization"),
            "content_type": self.headers.get("Content-Type"),
            "payload": json.loads(self.rfile.read(int(self.headers["Content-Length"]))),
        })
        self.send_response(self.server.reply_status)
        self.send_header("Content-Type", "application/json")
        self.end_headers()
        self.wfile.write(self.server.reply_body)

    def log_message(self, *_args):
        pass


def unused_port():
    with socket.socket() as sock:
        sock.bind(("127.0.0.1", 0))
        return sock.getsockname()[1]


def mock_reply(mock, payload, status=200):
    mock.calls.clear()
    mock.reply_status = status
    mock.reply_body = payload if isinstance(payload, bytes) else json.dumps(payload).encode()


@contextlib.contextmanager
def guest_api(web, folder, overrides):
    port = unused_port()
    base = f"http://127.0.0.1:{port}/"
    # Guest login/chat must not need a database, including when it is unavailable.
    config = {"db_host": "127.0.0.1", "db_port": "1", "news_sources": [], "app_url": base}
    config.update(overrides)
    encoded = base64.b64encode(json.dumps(config).encode()).decode()
    (web / "config.local.php").write_text(
        "<?php return json_decode(base64_decode('" + encoded + "'), true);", encoding="utf-8"
    )
    error_log = folder / "php-errors.log"
    error_log.write_text("", encoding="utf-8")
    sessions = folder / "sessions"
    sessions.mkdir(exist_ok=True)
    command = [os.environ.get("HC_PHP_BIN", "php")]
    if os.environ.get("HC_PHP_INI"):
        command += ["-c", os.environ["HC_PHP_INI"]]
    command += [
        "-d", f"session.save_path={sessions}",
        "-d", "log_errors=1", "-d", f"error_log={error_log}",
        "-S", f"127.0.0.1:{port}", "-t", str(web), str(web / "tools/router.php"),
    ]
    with (folder / "server.log").open("w") as server_log:
        server = subprocess.Popen(command, stdout=server_log, stderr=server_log)
        session = requests.Session()
        # All test traffic is loopback; no request carries a real credential.
        session.trust_env = False
        try:
            for _ in range(100):
                if server.poll() is not None:
                    raise RuntimeError("Test PHP server exited before readiness")
                try:
                    page = session.get(base + "dang-nhap.php", timeout=1)
                    if page.status_code == 200:
                        break
                except requests.RequestException:
                    pass
                time.sleep(0.05)
            else:
                raise RuntimeError("Test PHP server did not become ready")
            token = re.search(r'name="csrf-token" content="([^"]+)"', page.text)
            if not token:
                raise AssertionError("Guest login page must provide a CSRF token without a database")
            yield session, base, {"X-CSRF-Token": token[1]}, error_log
        finally:
            session.close()
            server.terminate()
            try:
                server.wait(timeout=5)
            except subprocess.TimeoutExpired:
                server.kill()
                server.wait(timeout=5)


def ask(session, base, headers, question=QUESTION):
    return session.post(base + "api/chat.php", json={"message": question}, headers=headers, timeout=10)


def run():
    mock = ThreadingHTTPServer(("127.0.0.1", 0), MockAI)
    mock.calls = []
    thread = threading.Thread(target=mock.serve_forever, daemon=True)
    thread.start()
    endpoint = f"http://127.0.0.1:{mock.server_address[1]}/model-test"
    try:
        with tempfile.TemporaryDirectory(prefix="healthconnect-cloud-") as temporary:
            folder = Path(temporary)
            web = folder / "site"
            shutil.copytree(ROOT / "website-yte-suckhoe", web,
                            ignore=shutil.ignore_patterns("config.local.php"))

            for provider, model in [("groq", "llama-3.3-70b-versatile"), ("gemini", "gemini-2.5-flash")]:
                answer = f"Mock {provider}: ngủ đủ giấc."
                mock_reply(mock, {"choices": [{"message": {"content": answer}}]})
                config = {"ai_provider": provider, "ai_api_key": TEST_KEY, "ai_url": endpoint}
                with guest_api(web, folder, config) as (session, base, headers, _log):
                    response = ask(session, base, headers)
                    check(response.status_code == 200, f"{provider} guest HTTP 200")
                    check(response.json() == {"answer": answer + DISCLAIMER, "session_id": 0},
                          f"{provider} answer, unchanged disclaimer and guest session id")
                    check(len(mock.calls) == 1, f"{provider} calls configured endpoint once")
                    call = mock.calls[0]
                    check(call["path"] == "/model-test", f"{provider} ai_url override")
                    check(call["authorization"] == "Bearer " + TEST_KEY, f"{provider} Bearer header")
                    check(call["content_type"] == "application/json", f"{provider} JSON content type")
                    check(call["payload"]["model"] == model, f"{provider} default model")
                    check(call["payload"]["stream"] is False, f"{provider} non-streaming payload")
                    check(call["payload"]["messages"][0]["role"] == "system"
                          and "Không chẩn đoán" in call["payload"]["messages"][0]["content"],
                          f"{provider} health system prompt retained")
                    check(call["payload"]["messages"][-1] == {"role": "user", "content": QUESTION},
                          f"{provider} forwards guest question")

            mock_reply(mock, {"choices": [{"message": {"content": "Override response"}}]})
            with guest_api(web, folder, {"ai_provider": "  GrOq  ", "ai_api_key": TEST_KEY,
                                        "ai_url": endpoint, "ai_model": "test-custom-model"}) as args:
                response = ask(*args[:3])
                check(response.status_code == 200 and mock.calls[0]["payload"]["model"] == "test-custom-model",
                      "provider is trimmed/lowercased and ai_model overrides default")

            for provider in ["ollama", "", None]:
                mock_reply(mock, {"message": {"content": "Mock Ollama response"}})
                config = {"ollama_url": endpoint, "ollama_model": "qwen2.5:3b",
                          "ai_api_key": TEST_KEY, "ai_model": "must-not-affect-ollama",
                          "ai_url": "http://127.0.0.1:1/must-not-call"}
                if provider is not None:
                    config["ai_provider"] = provider
                label = repr(provider) if provider is not None else "missing"
                with guest_api(web, folder, config) as args:
                    response = ask(*args[:3])
                    check(response.status_code == 200 and response.json()["answer"] == "Mock Ollama response" + DISCLAIMER,
                          f"Ollama provider {label} keeps message.content response")
                    check(len(mock.calls) == 1 and mock.calls[0]["authorization"] is None,
                          f"Ollama provider {label} never sends Authorization")
                    check(mock.calls[0]["payload"]["model"] == "qwen2.5:3b"
                          and mock.calls[0]["path"] == "/model-test",
                          f"Ollama provider {label} uses legacy URL/model")

            mock_reply(mock, {"error": {"message": "UPSTREAM_PRIVATE_RATE_LIMIT"}}, 429)
            with guest_api(web, folder, {"ai_provider": "groq", "ai_api_key": TEST_KEY, "ai_url": endpoint}) as args:
                response = ask(*args[:3])
                check(response.status_code == 429, "upstream HTTP 429 reaches guest as HTTP 429")
                check("phút" in response.json()["error"] and "giới hạn" in response.json()["error"]
                      and "UPSTREAM_PRIVATE_RATE_LIMIT" not in response.text,
                      "429 gives Vietnamese retry guidance without upstream error")

            for provider in ["groq", "gemini"]:
                mock_reply(mock, {"choices": [{"message": {"content": "must-not-call"}}]})
                with guest_api(web, folder, {"ai_provider": provider, "ai_url": endpoint}) as args:
                    response = ask(*args[:3])
                    check(response.status_code == 503 and response.json() == {"error": MISSING_KEY_ERROR},
                          f"{provider} missing key gives exact HTTP 503 error")
                    check(not mock.calls, f"{provider} missing key does not call upstream")

            mock_reply(mock, {})
            with guest_api(web, folder, {"ai_provider": "unknown", "ai_api_key": TEST_KEY, "ai_url": endpoint}) as args:
                response = ask(*args[:3])
                check(response.status_code == 503 and isinstance(response.json().get("error"), str),
                      "unknown provider gives JSON HTTP 503")
                check(not mock.calls, "unknown provider does not call upstream")

            # Both plain and JSON-escaped echoes must be removed from server logs.
            redaction_key = 'test-only/slash"quote\\marker'
            for payload, status, label in [
                ({"error": "UPSTREAM_PRIVATE_FAILURE " + redaction_key}, 500, "HTTP 500"),
                ({"choices": [{"message": {"content": "   "}}]}, 200, "empty answer"),
                (b"not-json UPSTREAM_PRIVATE_FAILURE", 200, "invalid JSON"),
            ]:
                mock_reply(mock, payload, status)
                with guest_api(web, folder, {"ai_provider": "groq", "ai_api_key": redaction_key, "ai_url": endpoint}) as args:
                    response = ask(*args[:3])
                    check(response.status_code == 503 and isinstance(response.json().get("error"), str),
                          f"{label} gives generic JSON HTTP 503")
                    check(redaction_key not in response.text and "UPSTREAM_PRIVATE_FAILURE" not in response.text,
                          f"{label} hides provider details from guest")
                    log = args[3].read_text(encoding="utf-8")
                    check("groq" in log and str(status) in log, f"{label} logs provider and HTTP code")
                    check(redaction_key not in log and json.dumps(redaction_key)[1:-1] not in log,
                          f"{label} does not log plain/JSON escaped API key")
                    check("UPSTREAM_PRIVATE_FAILURE" in log if label != "empty answer" else "content" in log,
                          f"{label} logs diagnostic response")

            mock_reply(mock, {"choices": [{"message": {"content": "Guest history response"}}]})
            with guest_api(web, folder, {"ai_provider": "groq", "ai_api_key": TEST_KEY, "ai_url": endpoint}) as args:
                session, base, headers, _log = args
                check(session.get(base + "api/chat.php", timeout=10).status_code == 405, "GET remains HTTP 405")
                check(ask(session, base, {}).status_code == 403, "missing CSRF remains HTTP 403")
                check(ask(session, base, headers, "").status_code == 422, "empty input remains HTTP 422")
                check(ask(session, base, headers, "a" * 2001).status_code == 422, "overlong input remains HTTP 422")
                check(not mock.calls, "invalid guest requests never call upstream")
                first = ask(session, base, headers)
                check(first.status_code == 200, "first valid guest message succeeds")
                check(ask(session, base, headers).status_code == 429 and len(mock.calls) == 1,
                      "three-second guest spam limit retained")
                time.sleep(3.1)
                response = ask(session, base, headers, "Còn vận động thì sao?")
                check(response.status_code == 200, "guest can continue after spam window")
                history = mock.calls[-1]["payload"]["messages"]
                check([item["role"] for item in history] == ["system", "user", "assistant", "user"]
                      and history[1]["content"] == QUESTION
                      and history[2]["content"] == first.json()["answer"],
                      "guest session preserves preceding question and answer with disclaimer")
            print(f"{checks} guest cloud chatbot checks passed")
    finally:
        mock.shutdown()
        mock.server_close()
        thread.join(timeout=5)


if __name__ == "__main__":
    run()
