import { useEffect, type ReactNode } from "react";
import { createPortal } from "react-dom";
import { AnimatePresence, motion } from "framer-motion";
import { X } from "lucide-react";
import { useLockBodyScroll } from "../../../hooks/useLockBodyScroll";
import { cn } from "../../../utils/cn";
import { useTheme } from "../../context/ThemeContext";

interface DrawerProps {
  open: boolean;
  onClose: () => void;
  title?: string;
  children: ReactNode;
  width?: string;
}

export function Drawer({ open, onClose, title, children, width = "max-w-md" }: DrawerProps) {
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
        <motion.div className="fixed inset-0 z-[100]" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }}>
          <motion.div
            className="absolute inset-0 bg-black/50 backdrop-blur-sm"
            onClick={onClose}
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
          />
          <motion.div
            initial={{ x: "100%" }}
            animate={{ x: 0 }}
            exit={{ x: "100%" }}
            transition={{ duration: 0.28, ease: [0.22, 1, 0.36, 1] }}
            className={cn(
              "admin-root absolute right-0 top-0 flex h-full w-full flex-col border-l border-a-border bg-a-surface shadow-2xl dark:border-a-dark-border dark:bg-a-dark-surface",
              theme === "dark" && "admin-dark",
              width,
            )}
          >
            <div className="flex items-center justify-between border-b border-a-border px-6 py-4 dark:border-a-dark-border">
              <h2 className="text-base font-semibold text-a-text dark:text-a-dark-text">{title}</h2>
              <button
                onClick={onClose}
                className="flex h-8 w-8 items-center justify-center rounded-lg text-a-muted transition-colors hover:bg-a-surface-2 hover:text-a-text dark:text-a-dark-muted dark:hover:bg-a-dark-surface-2 dark:hover:text-a-dark-text"
              >
                <X size={16} />
              </button>
            </div>
            <div className="flex-1 overflow-y-auto px-6 py-5">{children}</div>
          </motion.div>
        </motion.div>
      )}
    </AnimatePresence>,
    document.body,
  );
}
