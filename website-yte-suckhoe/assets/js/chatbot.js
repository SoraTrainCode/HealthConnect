/* chatbot.js - Đã tích hợp AI Local (Ollama) */

const DISCLAIMER = "Thông tin trên chỉ mang tính tham khảo. Vui lòng đến cơ sở y tế để được thăm khám và tư vấn chính xác.";

// Cấu hình AI
const AI_CONFIG = {
    URL: 'http://localhost:11434/api/chat',
    MODEL: 'qwen2.5:3b', // Đổi tên model đúng với model bạn đã tải trong máy
    SYSTEM_PROMPT: "Bạn là trợ lý y tế ảo của Sổ Tay Sức Khỏe. Nhiệm vụ của bạn là tư vấn thông tin sức khỏe cơ bản, giải thích triệu chứng bệnh và đưa ra lời khuyên sơ cứu tại nhà. Trả lời ngắn gọn, súc tích bằng tiếng Việt thân thiện, dùng xưng hô 'mình' và 'bạn'. Tuyệt đối KHÔNG được kê đơn thuốc. Nếu bệnh nặng, hãy khuyên người dùng đi khám bác sĩ."
};

// Mảng lưu trữ lịch sử trò chuyện để AI "nhớ" ngữ cảnh
let chatHistory = [];

function renderChatbotWidget() {
    const wrap = document.createElement("div");
    wrap.innerHTML = `
    <button id="chatbot-toggle" aria-label="Mở hộp thoại hỗ trợ">💬</button>
    <div id="chatbot-window" hidden>
        <div class="chatbot-head">
            <div>
                <strong>Trợ lý sức khỏe</strong>
                <small>AI Hỏi nhanh, đáp nhanh</small>
            </div>
            <button class="btn-close btn-close-white" aria-label="Đóng" onclick="toggleChatbot(false)"></button>
        </div>
        <div class="chatbot-body" id="chatbotBody">
            <div class="chat-bubble bot">Chào bạn 👋 Mình là trợ lý AI của Sổ Tay Sức Khỏe. Bạn đang gặp vấn đề gì về sức khỏe cần mình tư vấn không?</div>
            <div class="chat-bubble disclaimer">${DISCLAIMER}</div>
        </div>
        <form class="chatbot-foot" id="chatbotForm">
            <input type="text" id="chatbotInput" placeholder="Nhập câu hỏi của bạn..." autocomplete="off" required />
            <button type="submit" id="chatbotSubmit" aria-label="Gửi">➤</button>
        </form>
    </div>`;
    
    document.body.appendChild(wrap);
    document.getElementById("chatbot-toggle").addEventListener("click", () => toggleChatbot());
    document.getElementById("chatbotForm").addEventListener("submit", handleChatSubmit);
}

function toggleChatbot(force) {
    const win = document.getElementById("chatbot-window");
    const shouldShow = force === undefined ? win.hasAttribute("hidden") : force;
    if (shouldShow) {
        win.removeAttribute("hidden");
        document.getElementById("chatbotInput").focus();
    } else {
        win.setAttribute("hidden", "");
    }
}

async function handleChatSubmit(e) {
    e.preventDefault();
    const input = document.getElementById("chatbotInput");
    const submitBtn = document.getElementById("chatbotSubmit");
    const text = input.value.trim();
    if (!text) return;

    // 1. In câu hỏi của User ra màn hình
    appendBubble(text, "user");
    input.value = "";

    // 2. Khóa input và hiện trạng thái "đang gõ..."
    input.disabled = true;
    submitBtn.disabled = true;
    
    const body = document.getElementById("chatbotBody");
    const typing = document.createElement("div");
    typing.className = "chat-bubble bot typing-indicator";
    typing.textContent = "AI đang suy nghĩ...";
    body.appendChild(typing);
    body.scrollTop = body.scrollHeight;

    // 3. Gọi AI xử lý
    const botReply = await getAILocalReply(text);

    // 4. Xóa chữ "AI đang suy nghĩ...", in câu trả lời của AI ra
    typing.remove();
    appendBubble(botReply, "bot");
    appendBubble(DISCLAIMER, "disclaimer"); // Luôn chèn cảnh báo y tế sau câu của AI

    // 5. Mở khóa input
    input.disabled = false;
    submitBtn.disabled = false;
    input.focus();
}

function appendBubble(text, type) {
    const body = document.getElementById("chatbotBody");
    const el = document.createElement("div");
    el.className = "chat-bubble " + type;
    el.textContent = text;
    body.appendChild(el);
    body.scrollTop = body.scrollHeight;
}

// Hàm kết nối trực tiếp với Ollama chạy ở Local
async function getAILocalReply(userMessage) {
    // Thêm câu hỏi của user vào bộ nhớ ngữ cảnh
    chatHistory.push({ role: "user", content: userMessage });

    // Cấu trúc dữ liệu gửi cho Ollama
    const payload = {
        model: AI_CONFIG.MODEL,
        messages: [
            { role: "system", content: AI_CONFIG.SYSTEM_PROMPT },
            ...chatHistory // Chèn toàn bộ lịch sử trò chuyện vào đây
        ],
        stream: false
    };

    try {
        const response = await fetch(AI_CONFIG.URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        if (!response.ok) throw new Error("Server AI phản hồi lỗi");

        const data = await response.json();
        const aiMessage = data.message.content;

        // Thêm câu trả lời của AI vào bộ nhớ ngữ cảnh
        chatHistory.push({ role: "assistant", content: aiMessage });

        // Nếu lịch sử dài quá (hơn 10 tin nhắn), xóa bớt tin nhắn cũ (trừ system prompt) để tránh nặng bộ nhớ
        if (chatHistory.length > 10) {
            chatHistory = chatHistory.slice(chatHistory.length - 10);
        }

        return aiMessage;

    } catch (error) {
        console.error("Lỗi kết nối AI:", error);
        // Xóa câu user vừa hỏi khỏi lịch sử vì bị lỗi không có trả lời
        chatHistory.pop(); 
        return "Xin lỗi, hiện tại trợ lý AI đang bị mất kết nối (Ollama chưa chạy). Vui lòng kiểm tra lại server local của bạn nhé!";
    }
}

document.addEventListener("DOMContentLoaded", renderChatbotWidget);