import { useRef, useState, type FormEvent } from "react";
import { Sparkles, Send, Lock } from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { Button } from "../components/ui/Button";
import { Spinner } from "../components/ui/LoadingState";
import { AIUsageIndicator } from "../components/ai/AIUsageIndicator";
import { aiService } from "../services/aiService";
import { ApiError } from "../services/apiClient";

const EXAMPLE_QUESTIONS = [
  "How many active members do we have?",
  "How many memberships expire this week?",
  "What was this month's revenue?",
  "Which classes are most popular?",
  "Which equipment needs maintenance?",
  "Which members have low attendance?",
];

interface ChatEntry {
  id: string;
  question: string;
  answer?: string;
  error?: string;
}

export default function AIAssistant() {
  const [question, setQuestion] = useState("");
  const [entries, setEntries] = useState<ChatEntry[]>([]);
  const [loading, setLoading] = useState(false);
  const [limitReached, setLimitReached] = useState(false);
  const nextId = useRef(0);

  async function ask(text: string) {
    const trimmed = text.trim();
    if (!trimmed || loading) return;

    const id = `${nextId.current++}`;
    setEntries((e) => [...e, { id, question: trimmed }]);
    setQuestion("");
    setLoading(true);

    try {
      const result = await aiService.ask(trimmed);
      setEntries((e) => e.map((entry) => (entry.id === id ? { ...entry, answer: result.answer } : entry)));
    } catch (err) {
      if (err instanceof ApiError && err.status === 402) {
        setLimitReached(true);
        setEntries((e) => e.filter((entry) => entry.id !== id));
      } else {
        const message = err instanceof ApiError ? err.message : "Something went wrong. Please try again.";
        setEntries((e) => e.map((entry) => (entry.id === id ? { ...entry, error: message } : entry)));
      }
    } finally {
      setLoading(false);
    }
  }

  function handleSubmit(e: FormEvent) {
    e.preventDefault();
    void ask(question);
  }

  return (
    <div>
      <PageHeader
        title="AI Assistant"
        description="Ask questions about your gym — answered only from your own data."
        action={<AIUsageIndicator />}
      />

      <div className="admin-card flex h-[65vh] flex-col rounded-2xl shadow-sm">
        <div className="flex-1 overflow-y-auto p-5">
          {entries.length === 0 ? (
            <div className="flex h-full flex-col items-center justify-center gap-4 text-center">
              <div className="flex h-12 w-12 items-center justify-center rounded-full bg-a-accent/10 text-a-accent-2 dark:text-a-accent">
                <Sparkles size={20} />
              </div>
              <div>
                <p className="text-sm font-medium text-a-text dark:text-a-dark-text">Ask the assistant anything about your gym</p>
                <p className="mt-1 text-xs text-a-muted dark:text-a-dark-muted">Try one of these, or type your own question below.</p>
              </div>
              <div className="flex flex-wrap justify-center gap-2">
                {EXAMPLE_QUESTIONS.map((q) => (
                  <button
                    key={q}
                    onClick={() => void ask(q)}
                    className="rounded-full border border-a-border px-3 py-1.5 text-xs text-a-muted transition-colors hover:border-a-accent hover:text-a-text dark:border-a-dark-border dark:text-a-dark-muted dark:hover:text-a-dark-text"
                  >
                    {q}
                  </button>
                ))}
              </div>
            </div>
          ) : (
            <div className="space-y-5">
              {entries.map((entry) => (
                <div key={entry.id} className="space-y-2">
                  <div className="flex justify-end">
                    <p className="max-w-[80%] rounded-2xl rounded-br-sm bg-a-accent/15 px-4 py-2.5 text-sm text-a-text dark:text-a-dark-text">
                      {entry.question}
                    </p>
                  </div>
                  <div className="flex justify-start">
                    {entry.error ? (
                      <p className="max-w-[80%] rounded-2xl rounded-bl-sm border border-rose-500/30 bg-rose-500/10 px-4 py-2.5 text-sm text-rose-500">
                        {entry.error}
                      </p>
                    ) : entry.answer ? (
                      <p className="max-w-[80%] rounded-2xl rounded-bl-sm bg-a-surface-2 px-4 py-2.5 text-sm text-a-text dark:bg-a-dark-surface-2 dark:text-a-dark-text">
                        {entry.answer}
                      </p>
                    ) : (
                      <div className="flex items-center gap-2 rounded-2xl rounded-bl-sm bg-a-surface-2 px-4 py-2.5 dark:bg-a-dark-surface-2">
                        <Spinner size={14} />
                        <span className="text-xs text-a-muted dark:text-a-dark-muted">Thinking…</span>
                      </div>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        <div className="border-t border-a-border p-4 dark:border-a-dark-border">
          {limitReached ? (
            <div className="flex items-center gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-600 dark:text-amber-400">
              <Lock size={15} className="shrink-0" />
              Your plan's AI usage limit has been reached for this billing period. Upgrade your subscription to keep using the assistant.
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="flex items-center gap-2">
              <input
                value={question}
                onChange={(e) => setQuestion(e.target.value)}
                placeholder="Ask about members, revenue, classes, equipment..."
                disabled={loading}
                className="flex-1 rounded-xl border border-a-border bg-a-surface px-3.5 py-2.5 text-sm text-a-text outline-none transition-colors placeholder:text-a-muted focus:border-a-accent disabled:opacity-60 dark:border-a-dark-border dark:bg-a-dark-surface-2 dark:text-a-dark-text dark:placeholder:text-a-dark-muted"
              />
              <Button type="submit" icon={<Send size={15} />} disabled={loading || !question.trim()}>
                Ask
              </Button>
            </form>
          )}
        </div>
      </div>
    </div>
  );
}
