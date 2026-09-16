import { useEffect, type ReactNode } from "react";
import { createPortal } from "react-dom";
import { AnimatePresence, motion } from "framer-motion";
import { X } from "lucide-react";
import { useLockBodyScroll } from "../../../hooks/useLockBodyScroll";
import { cn } from "../../../utils/cn";
import { useTheme } from "../../context/ThemeContext";

interface ModalProps {
  open: boolean;
  onClose: () => void;
  title?: string;
  description?: string;
  children: ReactNode;
  footer?: ReactNode;
  size?: "sm" | "md" | "lg" | "xl";
}

const sizeMap = { sm: "max-w-md", md: "max-w-xl", lg: "max-w-3xl", xl: "max-w-5xl" };

export function Modal({ open, onClose, title, description, children, footer, size = "md" }: ModalProps) {
  useLockBodyScroll(open);
  const { theme } = useTheme();

  useEffect(() => {
    function onKey(e: KeyboardEvent) {
      if (e.key === "Escape") onClose();
    }
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, [onClose]);

  return createPortal(
    <AnimatePresence>
      {open && (
        <motion.div
          className="fixed inset-0 z-[100] flex items-center justify-center p-4"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
        >
          <motion.div
            className="absolute inset-0 bg-black/50 backdrop-blur-sm"
            onClick={onClose}
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
          />
          <motion.div
            role="dialog"
            aria-modal="true"
            initial={{ opacity: 0, scale: 0.95, y: 16 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.97, y: 8 }}
            transition={{ duration: 0.22, ease: [0.22, 1, 0.36, 1] }}
            className={cn(
              "admin-root relative z-10 max-h-[88vh] w-full overflow-y-auto rounded-2xl border border-a-border bg-a-surface shadow-2xl dark:border-a-dark-border dark:bg-a-dark-surface",
              theme === "dark" && "admin-dark",
              sizeMap[size],
            )}
          >
            <div className="sticky top-0 z-10 flex items-start justify-between border-b border-a-border bg-a-surface/95 px-6 py-4 backdrop-blur dark:border-a-dark-border dark:bg-a-dark-surface/95">
              <div>
                {title && <h2 className="text-base font-semibold text-a-text dark:text-a-dark-text">{title}</h2>}
                {description && <p className="mt-0.5 text-xs text-a-muted dark:text-a-dark-muted">{description}</p>}
              </div>
              <button
                onClick={onClose}
                aria-label="Close dialog"
                className="flex h-8 w-8 items-center justify-center rounded-lg text-a-muted transition-colors hover:bg-a-surface-2 hover:text-a-text dark:text-a-dark-muted dark:hover:bg-a-dark-surface-2 dark:hover:text-a-dark-text"
              >
                <X size={16} />
              </button>
            </div>
            <div className="px-6 py-5">{children}</div>
            {footer && (
              <div className="sticky bottom-0 flex items-center justify-end gap-2 border-t border-a-border bg-a-surface/95 px-6 py-4 backdrop-blur dark:border-a-dark-border dark:bg-a-dark-surface/95">
                {footer}
              </div>
            )}
          </motion.div>
        </motion.div>
      )}
    </AnimatePresence>,
    document.body,
  );
}
