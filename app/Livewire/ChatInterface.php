<?php

namespace App\Livewire;

use Livewire\Component;
use OpenAI;
use Livewire\Attributes\On;

class ChatInterface extends Component
{
    public $messages = [
        [
            'role' => 'system',
            'content' => 'You are a helpful assistant. The author name is Ahmad Rosid, I have over 7+ years of experience as a Software Engineer specializing in
            Backend development. Proficient in working with Golang and Google Cloud
            Infrastructure, successfully delivering efficient software solutions based on
            these technologies.
            Technologies: Golang, Javascript, Typescript, NodeJS, Docker, GCP, PostgreSQL,
            MySQL, Elasticsearch, Redis, KeyDB, Kafka and more. alahmadrosid@gmail.com',
        ],
    ];

    public $userInput = '';
    public $isWriting = false;

    public function sendMessage()
    {
        if ($this->userInput === '') {
            return;
        }

        if (count($this->messages) == 0) {
            $this->messages[] = ['role' => 'system', 'content' => 'You are a helpful assistant. The author name is Ahmad Rosid'];
        }

        $this->messages[] = [
            'role' => 'user',
            'content' => $this->userInput,
        ];
        $this->userInput = '';
        $this->isWriting = true;
        $this->dispatch('getAIStreamingResponse');
    }

    #[On('getAIStreamingResponse')]
    public function getAIStreamingResponse()
    {
        $client = OpenAI::factory()->withApiKey(env('OPENAI_API_KEY'))->make();

        $response = $client->chat()->createStreamed([
            'model' => 'gpt-3.5-turbo',
            'messages' => $this->messages,
        ]);

        $aiResponse = '';

        foreach ($response as $chunk) {
            $text = $chunk->choices[0]->delta->content ?? '';
            $aiResponse .= $text;

            $this->stream(
                to: 'ai-response',
                content: $aiResponse,
                replace: true,
            );
        }

        $this->messages[] = [
            'role' => 'assistant',
            'content' => $aiResponse,
        ];
        $this->isWriting = false;
    }

    public function render()
    {
        return <<<'HTML'
            <div class="flex flex-col border border-gray-300 bg-white rounded-md h-full overflow-hidden">
                <div class="flex-1 overflow-y-auto">
                    @if(count($messages) == 0)
                    <div class="flex items-center justify-center w-full h-full text-xl">
                        Ask any question about the document.
                    </div>
                    @endif
                    <div class="chat-messages flex flex-col">
                        @foreach ($messages as $message)
                        @if ($message['role'] == 'user')
                            <div class="message bg-gray-50 p-4 {{ $message['role'] }}">
                                <div class="font-semibold">
                                    You
                                </div>
                                <p>
                                    {{ $message['content'] }}
                                </p>
                            </div>
                        @elseif ($message['role'] == 'assistant')
                            <div class="message bg-gray-200/75 p-4">
                                <div class="font-semibold">
                                    Pdfpintar
                                </div>
                                <p>
                                    {{ $message['content'] }}
                                </p>
                            </div>
                        @endif
                        @endforeach

                        <div>
                            @if ($isWriting)
                            <div class="message bg-gray-200 p-4">
                                <div class="font-semibold">
                                    Pdfpintar
                                </div>
                                <div wire:stream="ai-response">Thinking...</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <form wire:submit.prevent="sendMessage" class="p-4">
                    <div class="flex items-end gap-2">
                        <textarea
                                x-data="{
                                    resize: () => {
                                        $el.style.height = '5px';
                                        $el.style.height = $el.scrollHeight + 'px';
                                    },
                                    handleSubmit: (e) => {
                                        if (e.key === 'Enter' && (e.metaKey || e.ctrlKey)) {
                                            $wire.sendMessage();
                                            e.preventDefault();
                                            e.target.value = '';
                                            $el.style.height = '5px';
                                            $el.style.height = $el.scrollHeight + 'px';
                                        }
                                    }
                                }"
                                @keydown="handleSubmit"
                                @paste="handlePaste"
                                @input="resize"
                                type="text" 
                                wire:model="userInput" 
                                placeholder="Type your message here..." 
                                rows="1"
                                class="resize-none flex w-full h-auto max-h-[400px] px-3 py-2 text-sm bg-white border rounded-md border-neutral-300 ring-offset-background placeholder:text-neutral-400 focus:border-neutral-300 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        ></textarea>
                        <div><button wire:click="sendMessage" class="bg-gray-800 hover:bg-gray-700 text-white py-2 px-3 rounded text-sm">Send</button></div>
                    </div>
                </form>
            </div>

            <script>
                document.addEventListener('livewire:load', function () {
                    window.addEventListener('user-message-added', function () {
                        const chatMessages = document.querySelector('.chat-messages');
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    });
                });
            </script>
        HTML;
    }
}
