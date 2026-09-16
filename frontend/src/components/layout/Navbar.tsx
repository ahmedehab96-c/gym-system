import { useState } from "react";
import { Link, NavLink as RouterNavLink } from "react-router-dom";
import { AnimatePresence, motion } from "framer-motion";
import { Dumbbell, Menu, X } from "lucide-react";
import { navLinks } from "../../data/nav";
import { Container } from "../ui/Container";
import { Button } from "../ui/Button";
import { useScrolled } from "../../hooks/useScrolled";
import { useLockBodyScroll } from "../../hooks/useLockBodyScroll";
import { cn } from "../../utils/cn";

export function Navbar() {
  const scrolled = useScrolled(30);
  const [open, setOpen] = useState(false);
  useLockBodyScroll(open);

  return (
    <header
      className={cn(
        "fixed inset-x-0 top-0 z-50 transition-all duration-300",
        scrolled || open ? "glass py-3 shadow-[0_10px_40px_-15px_rgba(0,0,0,0.6)]" : "bg-transparent py-5",
      )}
    >
      <Container className="flex items-center justify-between">
        <Link to="/" className="flex items-center gap-2.5" onClick={() => setOpen(false)}>
          <span className="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-gold-300 to-gold-600">
            <Dumbbell size={18} className="text-ink" />
          </span>
          <span className="font-display text-xl tracking-wide text-white">
            APEX <span className="text-gold-300">CLUB</span>
          </span>
        </Link>

        <nav className="hidden items-center gap-6 xl:flex">
          {navLinks.map((link) => (
            <RouterNavLink
              key={link.path}
              to={link.path}
              end={link.path === "/"}
              className={({ isActive }) =>
                cn(
                  "relative text-xs font-semibold uppercase tracking-[0.15em] transition-colors",
                  isActive ? "text-gold-300" : "text-white/75 hover:text-white",
                )
              }
            >
              {({ isActive }) => (
                <span className="relative pb-1">
                  {link.label}
                  {isActive && (
                    <motion.span
                      layoutId="nav-underline"
                      className="absolute -bottom-0.5 left-0 h-[2px] w-full bg-gold-400"
                    />
                  )}
                </span>
              )}
            </RouterNavLink>
          ))}
        </nav>

        <div className="hidden xl:block">
          <Button to="/membership" size="sm">
            Join Now
          </Button>
        </div>

        <button
          className="flex h-10 w-10 items-center justify-center rounded-full border border-white/15 text-white transition-colors hover:border-gold-400/50 xl:hidden"
          onClick={() => setOpen((v) => !v)}
          aria-label={open ? "Close menu" : "Open menu"}
          aria-expanded={open}
        >
          {open ? <X size={20} /> : <Menu size={20} />}
        </button>
      </Container>

      <AnimatePresence>
        {open && (
          <motion.div
            initial={{ opacity: 0, height: 0 }}
            animate={{ opacity: 1, height: "auto" }}
            exit={{ opacity: 0, height: 0 }}
            transition={{ duration: 0.35, ease: [0.22, 1, 0.36, 1] }}
            className="overflow-hidden xl:hidden"
          >
            <Container className="flex flex-col gap-1 pb-6 pt-4">
              {navLinks.map((link, i) => (
                <motion.div
                  key={link.path}
                  initial={{ opacity: 0, x: -16 }}
                  animate={{ opacity: 1, x: 0 }}
                  transition={{ delay: i * 0.04 }}
                >
                  <RouterNavLink
                    to={link.path}
                    end={link.path === "/"}
                    onClick={() => setOpen(false)}
                    className={({ isActive }) =>
                      cn(
                        "block border-b border-white/5 py-3 text-sm font-semibold uppercase tracking-wider",
                        isActive ? "text-gold-300" : "text-white/80",
                      )
                    }
                  >
                    {link.label}
                  </RouterNavLink>
                </motion.div>
              ))}
              <Button to="/membership" className="mt-4 w-full" onClick={() => setOpen(false)}>
                Join Now
              </Button>
            </Container>
          </motion.div>
        )}
      </AnimatePresence>
    </header>
  );
}
