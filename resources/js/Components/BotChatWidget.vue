<template>
  <div class="bot-chat-widget d-flex h-100">
    <!-- Sidebar: List of clients -->
    <div class="sidebar bg-light border-right p-2">
      <input v-model="search" type="text" class="form-control mb-3" placeholder="Search user..." />
      <div
        v-for="client in filteredClients"
        :key="client.chat_id"
        @click="selectClient(client.chat_id)"
        :class="[
          'client-item',
          'p-2 mb-2 rounded d-flex align-items-center justify-content-between',
          chatId === client.chat_id ? 'bg-primary text-white shadow-sm' : 'bg-white text-dark'
        ]"
        style="cursor:pointer;"
      >
        <div class="d-flex align-items-center">
          <img
            v-if="client.photo_url"
            :src="client.photo_url"
            alt="profile"
            width="36"
            height="36"
            class="rounded-circle mr-2 border"
            style="object-fit:cover;"
          />
          <div v-else class="default-avatar mr-2">
            <i class="fa fa-user"></i>
          </div>
          <span class="font-weight-bold" style="max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
            {{ client.name || client.chat_id }}
          </span>
        </div>
        <span v-if="client.unreadCount > 0" class="badge bg-danger ml-2">
          {{ client.unreadCount }}
        </span>
      </div>
    </div>
    <!-- Chat area -->
    <div class="chat-area card h-100 d-flex flex-column flex-grow-1">
      <div class="card-body overflow-auto chat-scroll" ref="chatContainer">
        <div v-for="msg in messages" :key="msg.id || msg.created_at" class="mb-3">
          <div :class="['d-inline-block px-3 py-2 rounded', msg.direction === 'incoming' ? 'bg-light text-dark' : 'bg-primary text-white float-right']">
            <div v-if="msg.type === 'text'"
                 style="white-space: pre-line; word-break: break-word;">
              {{ msg.message }}
            </div>
            <div v-else-if="msg.type === 'document'">
              <a :href="msg.file_url" class="text-white" target="_blank">📎 {{ msg.message }}</a>
            </div>
          </div>
          <div class="clearfix"></div>
          <small class="text-muted d-block" :class="msg.direction === 'incoming' ? 'text-left' : 'text-right'">
            {{ formatTimestamp(msg.created_at) }}
          </small>
        </div>
      </div>
      <div class="card-footer">
        <form @submit.prevent="sendMessage" class="d-flex flex-column">
          <textarea v-model="newMessage" rows="2" class="form-control mb-2" placeholder="Type a message..." :disabled="sending || !chatId"></textarea>
          <div class="d-flex justify-content-end">
            <button class="btn btn-primary" type="submit" :disabled="sending || !newMessage.trim() || !chatId">
              <span v-if="sending">Sending...</span>
              <span v-else>Send</span>
            </button>
          </div>
        </form>
        <div v-if="errorMsg" class="text-danger mt-2">{{ errorMsg }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import axios from 'axios'

const clients = ref([])
const chatId = ref(null)
const messages = ref([])
const newMessage = ref('')
const chatContainer = ref(null)
const sending = ref(false)
const errorMsg = ref('')
const search = ref('')

const fetchClients = async () => {
  const { data } = await axios.get('/api/telegram/clients')
  clients.value = data
  if (data.length && !chatId.value) {
    selectClient(data[0].chat_id)
  }
}

const fetchUnreadCounts = async () => {
  const { data } = await axios.get('/api/telegram/unread-counts')
  clients.value = clients.value.map(client => {
    const found = data.find(c => c.chat_id === client.chat_id)
    return { ...client, unreadCount: found ? found.unread_count : 0 }
  })
}

const fetchHistory = async () => {
  if (!chatId.value) {
    messages.value = []
    return
  }
  const { data } = await axios.get(`/api/telegram/history/${chatId.value}`)
  messages.value = data
  await nextTick(() => {
    if (chatContainer.value) {
      chatContainer.value.scrollTop = chatContainer.value.scrollHeight
    }
  })
  await axios.post('/api/telegram/mark-read', { chat_id: chatId.value })
  fetchUnreadCounts()
}

const selectClient = (id) => {
  chatId.value = id
  fetchHistory()
}

const sendMessage = async () => {
  if (!newMessage.value.trim() || sending.value || !chatId.value) return
  const text = newMessage.value
  newMessage.value = ''
  sending.value = true
  errorMsg.value = ''

  try {
    await axios.post('/api/telegram/send-message', {
      chat_id: chatId.value,
      message: text
    })

    messages.value.push({
      id: Date.now(),
      direction: 'outgoing',
      message: text,
      type: 'text',
      created_at: new Date().toISOString()
    })

    await nextTick(() => {
      if (chatContainer.value) {
        chatContainer.value.scrollTop = chatContainer.value.scrollHeight
      }
    })
    fetchUnreadCounts()
  } catch (e) {
    errorMsg.value = 'Failed to send message.'
    newMessage.value = text
  } finally {
    sending.value = false
  }
}

const formatTimestamp = (ts) => {
  const date = new Date(ts)
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const filteredClients = computed(() => {
  if (!search.value) return clients.value
  return clients.value.filter(client =>
    (client.name && client.name.toLowerCase().includes(search.value.toLowerCase())) ||
    (client.chat_id && client.chat_id.toLowerCase().includes(search.value.toLowerCase()))
  )
})

onMounted(async () => {
  await fetchClients()
  await fetchUnreadCounts()
})
</script>

<style scoped>
.bot-chat-widget {
  height: 600px;
  min-height: 600px;
  max-height: 600px;
  max-width: 100vw;
}
.sidebar {
  width: 220px;
  overflow-y: auto;
}
.chat-area {
  max-width: 480px;
  width: 480px;
  height: 100%;
  display: flex;
  flex-direction: column;
}
.card-body {
  flex: 1 1 auto;
  overflow-y: auto;
  padding-bottom: 0.5rem;
}
.card-footer {
  flex-shrink: 0;
  /* background: #fff; */
}
@media (max-width: 600px) {
  .bot-chat-widget {
    flex-direction: column !important;
    height: 100vh;
    min-height: 100vh;
    max-height: 100vh;
  }
  .sidebar {
    width: 100vw;
    max-width: 100vw;
    min-width: 0;
    border-right: none;
    border-bottom: 1px solid #dee2e6;
    flex-shrink: 0;
  }
  .chat-area {
    width: 100vw;
    max-width: 100vw;
    min-width: 0;
    height: 100%;
  }
  .card-body {
    max-height: 50vh;
  }
}
.client-item {
  transition: background 0.15s, box-shadow 0.15s;
}
.client-item:hover {
  background: #e9ecef !important;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.default-avatar {
  width: 36px;
  height: 36px;
  background: #dee2e6;
  color: #6c757d;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}
.chat-scroll {
  max-height: 420px;
  min-height: 100px;
  overflow-y: auto;
}
</style>