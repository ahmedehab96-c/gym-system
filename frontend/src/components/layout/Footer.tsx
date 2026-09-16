import { Link } from "react-router-dom";
import { Dumbbell, Mail, MapPin, Phone } from "lucide-react";
import { Container } from "../ui/Container";
import { navLinks } from "../../data/nav";
import { FacebookIcon, InstagramIcon, TwitterIcon, YoutubeIcon } from "../ui/SocialIcons";

const socials = [
  { icon: InstagramIcon, href: "#", label: "Instagram" },
  { icon: FacebookIcon, href: "#", label: "Facebook" },
  { icon: TwitterIcon, href: "#", label: "Twitter" },
  { icon: YoutubeIcon, href: "#", label: "YouTube" },
];

export function Footer() {
  return (
    <footer className="relative border-t border-white/10 bg-ink">
      <Container className="grid grid-cols-1 gap-12 py-16 sm:grid-cols-2 lg:grid-cols-4 lg:py-20">
        <div className="flex flex-col gap-5">
          <Link to="/" className="flex items-center gap-2.5">
            <span className="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-gold-300 to-gold-600">
              <Dumbbell size={18} className="text-ink" />
            </span>
            <span className="font-display text-xl tracking-wide text-white">
              APEX <span className="text-gold-300">CLUB</span>
            </span>
          </Link>
          <p className="max-w-xs text-sm leading-relaxed text-white/50">
            A premium bodybuilding and fitness club built for members who refuse to settle for average.
          </p>
          <div className="flex gap-3">
            {socials.map(({ icon: Icon, href, label }) => (
              <a
                key={label}
                href={href}
                aria-label={label}
                className="flex h-10 w-10 items-center justify-center rounded-full border border-white/10 text-white/70 transition-all hover:border-gold-400 hover:text-gold-300"
              >
                <Icon size={16} />
              </a>
            ))}
          </div>
        </div>

        <div>
          <h3 className="mb-5 text-xs font-semibold uppercase tracking-[0.25em] text-white/55">Explore</h3>
          <ul className="flex flex-col gap-3">
            {navLinks.map((link) => (
              <li key={link.path}>
                <Link to={link.path} className="text-sm text-white/65 transition-colors hover:text-gold-300">
                  {link.label}
                </Link>
              </li>
            ))}
          </ul>
        </div>

        <div>
          <h3 className="mb-5 text-xs font-semibold uppercase tracking-[0.25em] text-white/55">Opening Hours</h3>
          <ul className="flex flex-col gap-3 text-sm text-white/65">
            <li className="flex justify-between gap-6"><span>Mon – Fri</span><span>05:00 – 23:00</span></li>
            <li className="flex justify-between gap-6"><span>Saturday</span><span>06:00 – 22:00</span></li>
            <li className="flex justify-between gap-6"><span>Sunday</span><span>07:00 – 20:00</span></li>
            <li className="flex justify-between gap-6 text-gold-300"><span>Elite Members</span><span>24/7</span></li>
          </ul>
        </div>

        <div>
          <h3 className="mb-5 text-xs font-semibold uppercase tracking-[0.25em] text-white/55">Contact</h3>
          <ul className="flex flex-col gap-4 text-sm text-white/65">
            <li className="flex items-start gap-3">
              <MapPin size={16} className="mt-0.5 shrink-0 text-gold-400" />
              <span>128 Iron District, Downtown Metro</span>
            </li>
            <li className="flex items-center gap-3">
              <Phone size={16} className="shrink-0 text-gold-400" />
              <span>+1 (555) 210-8842</span>
            </li>
            <li className="flex items-center gap-3">
              <Mail size={16} className="shrink-0 text-gold-400" />
              <span>hello@apexclub.com</span>
            </li>
          </ul>
        </div>
      </Container>

      <div className="border-t border-white/5 py-6">
        <Container className="flex flex-col items-center justify-between gap-3 text-xs text-white/50 sm:flex-row">
          <p>© {new Date().getFullYear()} APEX Performance Club. All rights reserved.</p>
          <p>Frontend demo — mock data only.</p>
        </Container>
      </div>
    </footer>
  );
}
