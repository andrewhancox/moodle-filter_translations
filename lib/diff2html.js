/**
 * diff2html
 *
 * @author  Rodrigo Fernandes
 * @link https://github.com/rtfpessoa/diff2html
 * @license MIT, see https://github.com/rtfpessoa/diff2html/blob/master/LICENSE.md
 */

!function (e, n) {
    "object" == typeof exports && "object" == typeof module ? module.exports = n() : "function" == typeof define && define.amd ? define("Diff2Html", [], n) : "object" == typeof exports ? exports.Diff2Html = n() :
        e.Diff2Html = n()
}(this, (function () {
    return function (e) {
        var n = {};

        function t(i) {
            if (n[i]) return n[i].exports;
            var r = n[i] = {i: i, l: !1, exports: {}};
            return e[i].call(r.exports, r, r.exports, t), r.l = !0, r.exports
        }

        return t.m = e, t.c = n, t.d = function (e, n, i) {
            t.o(e, n) || Object.defineProperty(e, n, {enumerable: !0, get: i})
        }, t.r = function (e) {
            "undefined" != typeof Symbol && Symbol.toStringTag && Object.defineProperty(e, Symbol.toStringTag, {value: "Module"}), Object.defineProperty(e, "__esModule", {value: !0})
        }, t.t = function (e, n) {
            if (1 & n && (e = t(e)), 8 & n) return e;
            if (4 & n && "object" == typeof e && e && e.__esModule) return e;
            var i = Object.create(null);
            if (t.r(i), Object.defineProperty(i, "default", {enumerable: !0, value: e}), 2 & n && "string" != typeof e) for (var r in e) t.d(i, r, function (n) {
                return e[n]
            }.bind(null, r));
            return i
        }, t.n = function (e) {
            var n = e && e.__esModule ? function () {
                return e.default
            } : function () {
                return e
            };
            return t.d(n, "a", n), n
        }, t.o = function (e, n) {
            return Object.prototype.hasOwnProperty.call(e, n)
        }, t.p = "", t(t.s = 5)
    }([function (e, n, t) {
        "use strict";
        Object.defineProperty(n, "__esModule", {value: !0}), n.DiffStyleType = n.LineMatchingType = n.OutputFormatType = n.LineType = void 0, function (e) {
            e.INSERT = "insert", e.DELETE = "delete", e.CONTEXT = "context"
        }(n.LineType || (n.LineType = {})), n.OutputFormatType = {LINE_BY_LINE: "line-by-line", SIDE_BY_SIDE: "side-by-side"}, n.LineMatchingType = {
            LINES: "lines",
            WORDS: "words",
            NONE: "none"
        }, n.DiffStyleType = {WORD: "word", CHAR: "char"}
    }, function (e, n, t) {
        "use strict";
        var i = this && this.__assign || function () {
            return (i = Object.assign || function (e) {
                for (var n, t = 1, i = arguments.length; t < i; t++) for (var r in n = arguments[t]) Object.prototype.hasOwnProperty.call(n, r) && (e[r] = n[r]);
                return e
            }).apply(this, arguments)
        }, r = this && this.__createBinding || (Object.create ? function (e, n, t, i) {
            void 0 === i && (i = t), Object.defineProperty(e, i, {
                enumerable: !0, get: function () {
                    return n[t]
                }
            })
        } : function (e, n, t, i) {
            void 0 === i && (i = t), e[i] = n[t]
        }), o = this && this.__setModuleDefault || (Object.create ? function (e, n) {
            Object.defineProperty(e, "default", {enumerable: !0, value: n})
        } : function (e, n) {
            e.default = n
        }), a = this && this.__importStar || function (e) {
            if (e && e.__esModule) return e;
            var n = {};
            if (null != e) for (var t in e) "default" !== t && Object.prototype.hasOwnProperty.call(e, t) && r(n, e, t);
            return o(n, e), n
        };
        Object.defineProperty(n, "__esModule", {value: !0}), n.diffHighlight = n.getFileIcon = n.getHtmlId = n.filenameDiff = n.deconstructLine = n.escapeForHtml = n.toCSSClass = n.defaultRenderConfig = n.CSSLineClass = void 0;
        var s = a(t(8)), l = t(3), f = a(t(2)), u = t(0);
        n.CSSLineClass = {
            INSERTS: "d2h-ins",
            DELETES: "d2h-del",
            CONTEXT: "d2h-cntx",
            INFO: "d2h-info",
            INSERT_CHANGES: "d2h-ins d2h-change",
            DELETE_CHANGES: "d2h-del d2h-change"
        }, n.defaultRenderConfig = {matching: u.LineMatchingType.NONE, matchWordsThreshold: .25, maxLineLengthHighlight: 1e4, diffStyle: u.DiffStyleType.WORD};
        var d = f.newDistanceFn((function (e) {
            return e.value
        })), c = f.newMatcherFn(d);

        function h(e) {
            return -1 !== e.indexOf("dev/null")
        }

        function p(e) {
            return e.replace(/(<del[^>]*>((.|\n)*?)<\/del>)/g, "")
        }

        function b(e) {
            return e.slice(0).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#x27;").replace(/\//g, "&#x2F;")
        }

        function g(e, n, t) {
            void 0 === t && (t = !0);
            var i = function (e) {
                return e ? 2 : 1
            }(n);
            return {prefix: e.substring(0, i), content: t ? b(e.substring(i)) : e.substring(i)}
        }

        function v(e) {
            var n = l.unifyPath(e.oldName), t = l.unifyPath(e.newName);
            if (n === t || h(n) || h(t)) return h(t) ? n : t;
            for (var i = [], r = [], o = n.split("/"), a = t.split("/"), s = 0, f = o.length - 1, u = a.length - 1; s < f && s < u && o[s] === a[s];) i.push(a[s]), s += 1;
            for (; f > s && u > s && o[f] === a[u];) r.unshift(a[u]), f -= 1, u -= 1;
            var d = i.join("/"), c = r.join("/"), p = o.slice(s, f + 1).join("/"), b = a.slice(s, u + 1).join("/");
            return d.length && c.length ? d + "/{" + p + " → " + b + "}/" + c : d.length ? d + "/{" + p + " → " + b + "}" : c.length ? "{" + p + " → " + b + "}/" + c : n + " → " + t
        }

        n.toCSSClass = function (e) {
            switch (e) {
                case u.LineType.CONTEXT:
                    return n.CSSLineClass.CONTEXT;
                case u.LineType.INSERT:
                    return n.CSSLineClass.INSERTS;
                case u.LineType.DELETE:
                    return n.CSSLineClass.DELETES
            }
        }, n.escapeForHtml = b, n.deconstructLine = g, n.filenameDiff = v, n.getHtmlId = function (e) {
            return "d2h-" + l.hashCode(v(e)).toString().slice(-6)
        }, n.getFileIcon = function (e) {
            var n = "file-changed";
            return e.isRename || e.isCopy ? n = "file-renamed" : e.isNew ? n = "file-added" : e.isDeleted ? n = "file-deleted" : e.newName !== e.oldName && (n = "file-renamed"), n
        }, n.diffHighlight = function (e, t, r, o) {
            void 0 === o && (o = {});
            var a = i(i({}, n.defaultRenderConfig), o), l = a.matching, f = a.maxLineLengthHighlight, u = a.matchWordsThreshold, h = a.diffStyle, v = g(e, r, !1), m = g(t, r, !1);
            if (v.content.length > f || m.content.length > f) return {oldLine: {prefix: v.prefix, content: b(v.content)}, newLine: {prefix: m.prefix, content: b(m.content)}};
            var y = "char" === h ? s.diffChars(v.content, m.content) : s.diffWordsWithSpace(v.content, m.content), w = [];
            if ("word" === h && "words" === l) {
                var S = y.filter((function (e) {
                    return e.removed
                })), L = y.filter((function (e) {
                    return e.added
                }));
                c(L, S).forEach((function (e) {
                    1 === e[0].length && 1 === e[1].length && (d(e[0][0], e[1][0]) < u && (w.push(e[0][0]), w.push(e[1][0])))
                }))
            }
            var x, C = y.reduce((function (e, n) {
                var t = n.added ? "ins" : n.removed ? "del" : null, i = w.indexOf(n) > -1 ? ' class="d2h-change"' : "", r = b(n.value);
                return null !== t ? e + "<" + t + i + ">" + r + "</" + t + ">" : "" + e + r
            }), "");
            return {oldLine: {prefix: v.prefix, content: (x = C, x.replace(/(<ins[^>]*>((.|\n)*?)<\/ins>)/g, ""))}, newLine: {prefix: m.prefix, content: p(C)}}
        }
    }, function (e, n, t) {
        "use strict";

        function i(e, n) {
            if (0 === e.length) return n.length;
            if (0 === n.length) return e.length;
            var t, i, r = [];
            for (t = 0; t <= n.length; t++) r[t] = [t];
            for (i = 0; i <= e.length; i++) r[0][i] = i;
            for (t = 1; t <= n.length; t++) for (i = 1; i <= e.length; i++) n.charAt(t - 1) === e.charAt(i - 1) ? r[t][i] = r[t - 1][i - 1] : r[t][i] = Math.min(r[t - 1][i - 1] + 1, Math.min(r[t][i - 1] + 1, r[t - 1][i] + 1));
            return r[n.length][e.length]
        }

        Object.defineProperty(n, "__esModule", {value: !0}), n.newMatcherFn = n.newDistanceFn = n.levenshtein = void 0, n.levenshtein = i, n.newDistanceFn = function (e) {
            return function (n, t) {
                var r = e(n).trim(), o = e(t).trim();
                return i(r, o) / (r.length + o.length)
            }
        }, n.newMatcherFn = function (e) {
            return function n(t, i, r, o) {
                void 0 === r && (r = 0), void 0 === o && (o = new Map);
                var a = function (n, t, i) {
                    void 0 === i && (i = new Map);
                    for (var r, o = 1 / 0, a = 0; a < n.length; ++a) for (var s = 0; s < t.length; ++s) {
                        var l = JSON.stringify([n[a], t[s]]), f = void 0;
                        i.has(l) && (f = i.get(l)) || (f = e(n[a], t[s]), i.set(l, f)), f < o && (r = {indexA: a, indexB: s, score: o = f})
                    }
                    return r
                }(t, i, o);
                if (!a || t.length + i.length < 3) return [[t, i]];
                var s = t.slice(0, a.indexA), l = i.slice(0, a.indexB), f = [t[a.indexA]], u = [i[a.indexB]], d = a.indexA + 1, c = a.indexB + 1, h = t.slice(d), p = i.slice(c),
                    b = n(s, l, r + 1, o), g = n(f, u, r + 1, o), v = n(h, p, r + 1, o), m = g;
                return (a.indexA > 0 || a.indexB > 0) && (m = b.concat(m)), (t.length > d || i.length > c) && (m = m.concat(v)), m
            }
        }
    }, function (e, n, t) {
        "use strict";
        Object.defineProperty(n, "__esModule", {value: !0}), n.hashCode = n.unifyPath = n.escapeForRegExp = void 0;
        var i = RegExp("[" + ["-", "[", "]", "/", "{", "}", "(", ")", "*", "+", "?", ".", "\\", "^", "$", "|"].join("\\") + "]", "g");
        n.escapeForRegExp = function (e) {
            return e.replace(i, "\\$&")
        }, n.unifyPath = function (e) {
            return e ? e.replace(/\\/g, "/") : e
        }, n.hashCode = function (e) {
            var n, t, i = 0;
            for (n = 0, t = e.length; n < t; n++) i = (i << 5) - i + e.charCodeAt(n), i |= 0;
            return i
        }
    }, function (e, n, t) {
        var i = t(12);
        i.Template = t(13).Template, i.template = i.Template, e.exports = i
    }, function (e, n, t) {
        "use strict";
        var i = this && this.__assign || function () {
            return (i = Object.assign || function (e) {
                for (var n, t = 1, i = arguments.length; t < i; t++) for (var r in n = arguments[t]) Object.prototype.hasOwnProperty.call(n, r) && (e[r] = n[r]);
                return e
            }).apply(this, arguments)
        }, r = this && this.__createBinding || (Object.create ? function (e, n, t, i) {
            void 0 === i && (i = t), Object.defineProperty(e, i, {
                enumerable: !0, get: function () {
                    return n[t]
                }
            })
        } : function (e, n, t, i) {
            void 0 === i && (i = t), e[i] = n[t]
        }), o = this && this.__setModuleDefault || (Object.create ? function (e, n) {
            Object.defineProperty(e, "default", {enumerable: !0, value: n})
        } : function (e, n) {
            e.default = n
        }), a = this && this.__importStar || function (e) {
            if (e && e.__esModule) return e;
            var n = {};
            if (null != e) for (var t in e) "default" !== t && Object.prototype.hasOwnProperty.call(e, t) && r(n, e, t);
            return o(n, e), n
        }, s = this && this.__importDefault || function (e) {
            return e && e.__esModule ? e : {default: e}
        };
        Object.defineProperty(n, "__esModule", {value: !0}), n.html = n.parse = n.defaultDiff2HtmlConfig = void 0;
        var l = a(t(6)), f = a(t(7)), u = a(t(9)), d = a(t(10)), c = t(0), h = s(t(11));
        n.defaultDiff2HtmlConfig = i(i(i({}, u.defaultLineByLineRendererConfig), d.defaultSideBySideRendererConfig), {
            outputFormat: c.OutputFormatType.LINE_BY_LINE,
            drawFileList: !0
        }), n.parse = function (e, t) {
            return void 0 === t && (t = {}), l.parse(e, i(i({}, n.defaultDiff2HtmlConfig), t))
        }, n.html = function (e, t) {
            void 0 === t && (t = {});
            var r = i(i({}, n.defaultDiff2HtmlConfig), t), o = "string" == typeof e ? l.parse(e, r) : e, a = new h.default(r);
            return (r.drawFileList ? f.render(o, a) : "") + ("side-by-side" === r.outputFormat ? new d.default(a, r).render(o) : new u.default(a, r).render(o))
        }
    }, function (e, n, t) {
        "use strict";
        var i = this && this.__spreadArrays || function () {
            for (var e = 0, n = 0, t = arguments.length; n < t; n++) e += arguments[n].length;
            var i = Array(e), r = 0;
            for (n = 0; n < t; n++) for (var o = arguments[n], a = 0, s = o.length; a < s; a++, r++) i[r] = o[a];
            return i
        };
        Object.defineProperty(n, "__esModule", {value: !0}), n.parse = void 0;
        var r = t(0), o = t(3);

        function a(e, n) {
            var t = e.split(".");
            return t.length > 1 ? t[t.length - 1] : n
        }

        function s(e, n) {
            return n.reduce((function (n, t) {
                return n || e.startsWith(t)
            }), !1)
        }

        var l = ["a/", "b/", "i/", "w/", "c/", "o/"];

        function f(e, n, t) {
            var r = void 0 !== t ? i(l, [t]) : l, a = ((n ? new RegExp("^" + o.escapeForRegExp(n) + ' "?(.+?)"?$') : new RegExp('^"?(.+?)"?$')).exec(e) || [])[1],
                s = void 0 === a ? "" : a, f = r.find((function (e) {
                    return 0 === s.indexOf(e)
                }));
            return (f ? s.slice(f.length) : s).replace(/\s+\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}(?:\.\d+)? [+-]\d{4}.*$/, "")
        }

        n.parse = function (e, n) {
            void 0 === n && (n = {});
            var t = [], i = null, o = null, l = null, u = null, d = null, c = null, h = null, p = /^old mode (\d{6})/, b = /^new mode (\d{6})/, g = /^deleted file mode (\d{6})/,
                v = /^new file mode (\d{6})/, m = /^copy from "?(.+)"?/, y = /^copy to "?(.+)"?/, w = /^rename from "?(.+)"?/, S = /^rename to "?(.+)"?/,
                L = /^similarity index (\d+)%/, x = /^dissimilarity index (\d+)%/, C = /^index ([\da-z]+)\.\.([\da-z]+)\s*(\d{6})?/, T = /^Binary files (.*) and (.*) differ/,
                N = /^GIT binary patch/, O = /^index ([\da-z]+),([\da-z]+)\.\.([\da-z]+)/, _ = /^mode (\d{6}),(\d{6})\.\.(\d{6})/, E = /^new file mode (\d{6})/,
                H = /^deleted file mode (\d{6}),(\d{6})/, j = e.replace(/\\ No newline at end of file/g, "").replace(/\r\n?/g, "\n").split("\n");

            function k() {
                null !== o && null !== i && (i.blocks.push(o), o = null)
            }

            function F() {
                null !== i && (i.oldName || null === c || (i.oldName = c), i.newName || null === h || (i.newName = h), i.newName && (t.push(i), i = null)), c = null, h = null
            }

            function P() {
                k(), F(), i = {blocks: [], deletedLines: 0, addedLines: 0}
            }

            function M(e) {
                var n;
                k(), null !== i && ((n = /^@@ -(\d+)(?:,\d+)? \+(\d+)(?:,\d+)? @@.*/.exec(e)) ? (i.isCombined = !1, l = parseInt(n[1], 10), d = parseInt(n[2], 10)) : (n = /^@@@ -(\d+)(?:,\d+)? -(\d+)(?:,\d+)? \+(\d+)(?:,\d+)? @@@.*/.exec(e)) ? (i.isCombined = !0, l = parseInt(n[1], 10), u = parseInt(n[2], 10), d = parseInt(n[3], 10)) : (e.startsWith("@@") && console.error("Failed to parse lines, starting in 0!"), l = 0, d = 0, i.isCombined = !1)), o = {
                    lines: [],
                    oldStartLine: l,
                    oldStartLine2: u,
                    newStartLine: d,
                    header: e
                }
            }

            return j.forEach((function (e, t) {
                if (e && !e.startsWith("*")) {
                    var u, k = j[t - 1], F = j[t + 1], I = j[t + 2];
                    if (e.startsWith("diff")) {
                        P();
                        if ((u = /^diff --git "?(.+)"? "?(.+)"?/.exec(e)) && (c = f(u[1], void 0, n.dstPrefix), h = f(u[2], void 0, n.srcPrefix)), null === i) throw new Error("Where is my file !!!");
                        i.isGitDiff = !0
                    } else {
                        if ((!i || !i.isGitDiff && i && e.startsWith("--- ") && F.startsWith("+++ ") && I.startsWith("@@")) && P(), e.startsWith("--- ") && F.startsWith("+++ ") || e.startsWith("+++ ") && k.startsWith("--- ")) {
                            if (i && !i.oldName && e.startsWith("--- ") && (u = function (e, n) {
                                return f(e, "---", n)
                            }(e, n.srcPrefix))) return i.oldName = u, void (i.language = a(i.oldName, i.language));
                            if (i && !i.newName && e.startsWith("+++ ") && (u = function (e, n) {
                                return f(e, "+++", n)
                            }(e, n.dstPrefix))) return i.newName = u, void (i.language = a(i.newName, i.language))
                        }
                        if (i && (e.startsWith("@@") || i.isGitDiff && i.oldName && i.newName && !o)) M(e); else if (o && (e.startsWith("+") || e.startsWith("-") || e.startsWith(" "))) !function (e) {
                            if (null !== i && null !== o && null !== l && null !== d) {
                                var n = {content: e}, t = i.isCombined ? ["+ ", " +", "++"] : ["+"], a = i.isCombined ? ["- ", " -", "--"] : ["-"];
                                s(e, t) ? (i.addedLines++, n.type = r.LineType.INSERT, n.oldNumber = void 0, n.newNumber = d++) : s(e, a) ? (i.deletedLines++, n.type = r.LineType.DELETE, n.oldNumber = l++, n.newNumber = void 0) : (n.type = r.LineType.CONTEXT, n.oldNumber = l++, n.newNumber = d++), o.lines.push(n)
                            }
                        }(e); else {
                            var D = !function (e, n) {
                                for (var t = n; t < j.length - 3;) {
                                    if (e.startsWith("diff")) return !1;
                                    if (j[t].startsWith("--- ") && j[t + 1].startsWith("+++ ") && j[t + 2].startsWith("@@")) return !0;
                                    t++
                                }
                                return !1
                            }(e, t);
                            if (null === i) throw new Error("Where is my file !!!");
                            (u = p.exec(e)) ? i.oldMode = u[1] : (u = b.exec(e)) ? i.newMode = u[1] : (u = g.exec(e)) ? (i.deletedFileMode = u[1], i.isDeleted = !0) : (u = v.exec(e)) ? (i.newFileMode = u[1], i.isNew = !0) : (u = m.exec(e)) ? (D && (i.oldName = u[1]), i.isCopy = !0) : (u = y.exec(e)) ? (D && (i.newName = u[1]), i.isCopy = !0) : (u = w.exec(e)) ? (D && (i.oldName = u[1]), i.isRename = !0) : (u = S.exec(e)) ? (D && (i.newName = u[1]), i.isRename = !0) : (u = T.exec(e)) ? (i.isBinary = !0, i.oldName = f(u[1], void 0, n.srcPrefix), i.newName = f(u[2], void 0, n.dstPrefix), M("Binary file")) : N.test(e) ? (i.isBinary = !0, M(e)) : (u = L.exec(e)) ? i.unchangedPercentage = parseInt(u[1], 10) : (u = x.exec(e)) ? i.changedPercentage = parseInt(u[1], 10) : (u = C.exec(e)) ? (i.checksumBefore = u[1], i.checksumAfter = u[2], u[3] && (i.mode = u[3])) : (u = O.exec(e)) ? (i.checksumBefore = [u[2], u[3]], i.checksumAfter = u[1]) : (u = _.exec(e)) ? (i.oldMode = [u[2], u[3]], i.newMode = u[1]) : (u = E.exec(e)) ? (i.newFileMode = u[1], i.isNew = !0) : (u = H.exec(e)) && (i.deletedFileMode = u[1], i.isDeleted = !0)
                        }
                    }
                }
            })), k(), F(), t
        }
    }, function (e, n, t) {
        "use strict";
        var i = this && this.__createBinding || (Object.create ? function (e, n, t, i) {
            void 0 === i && (i = t), Object.defineProperty(e, i, {
                enumerable: !0, get: function () {
                    return n[t]
                }
            })
        } : function (e, n, t, i) {
            void 0 === i && (i = t), e[i] = n[t]
        }), r = this && this.__setModuleDefault || (Object.create ? function (e, n) {
            Object.defineProperty(e, "default", {enumerable: !0, value: n})
        } : function (e, n) {
            e.default = n
        }), o = this && this.__importStar || function (e) {
            if (e && e.__esModule) return e;
            var n = {};
            if (null != e) for (var t in e) "default" !== t && Object.prototype.hasOwnProperty.call(e, t) && i(n, e, t);
            return r(n, e), n
        };
        Object.defineProperty(n, "__esModule", {value: !0}), n.render = void 0;
        var a = o(t(1));
        n.render = function (e, n) {
            var t = e.map((function (e) {
                return n.render("file-summary", "line", {
                    fileHtmlId: a.getHtmlId(e),
                    oldName: e.oldName,
                    newName: e.newName,
                    fileName: a.filenameDiff(e),
                    deletedLines: "-" + e.deletedLines,
                    addedLines: "+" + e.addedLines
                }, {fileIcon: n.template("icon", a.getFileIcon(e))})
            })).join("\n");
            return n.render("file-summary", "wrapper", {filesNumber: e.length, files: t})
        }
    }, function (e, n, t) {
        /*!

     diff v4.0.1

    Software License Agreement (BSD License)

    Copyright (c) 2009-2015, Kevin Decker <kpdecker@gmail.com>

    All rights reserved.

    Redistribution and use of this software in source and binary forms, with or without modification,
    are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above
      copyright notice, this list of conditions and the
      following disclaimer.

    * Redistributions in binary form must reproduce the above
      copyright notice, this list of conditions and the
      following disclaimer in the documentation and/or other
      materials provided with the distribution.

    * Neither the name of Kevin Decker nor the names of its
      contributors may be used to endorse or promote products
      derived from this software without specific prior
      written permission.

    THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR
    IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
    FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
    CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
    DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
    DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER
    IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT
    OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
    @license
    */
        !function (e) {
            "use strict";

            function n() {
            }

            function t(e, n, t, i, r) {
                for (var o = 0, a = n.length, s = 0, l = 0; o < a; o++) {
                    var f = n[o];
                    if (f.removed) {
                        if (f.value = e.join(i.slice(l, l + f.count)), l += f.count, o && n[o - 1].added) {
                            var u = n[o - 1];
                            n[o - 1] = n[o], n[o] = u
                        }
                    } else {
                        if (!f.added && r) {
                            var d = t.slice(s, s + f.count);
                            d = d.map((function (e, n) {
                                var t = i[l + n];
                                return t.length > e.length ? t : e
                            })), f.value = e.join(d)
                        } else f.value = e.join(t.slice(s, s + f.count));
                        s += f.count, f.added || (l += f.count)
                    }
                }
                var c = n[a - 1];
                return a > 1 && "string" == typeof c.value && (c.added || c.removed) && e.equals("", c.value) && (n[a - 2].value += c.value, n.pop()), n
            }

            function i(e) {
                return {newPos: e.newPos, components: e.components.slice(0)}
            }

            n.prototype = {
                diff: function (e, n) {
                    var r = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {}, o = r.callback;
                    "function" == typeof r && (o = r, r = {}), this.options = r;
                    var a = this;

                    function s(e) {
                        return o ? (setTimeout((function () {
                            o(void 0, e)
                        }), 0), !0) : e
                    }

                    e = this.castInput(e), n = this.castInput(n), e = this.removeEmpty(this.tokenize(e));
                    var l = (n = this.removeEmpty(this.tokenize(n))).length, f = e.length, u = 1, d = l + f, c = [{newPos: -1, components: []}],
                        h = this.extractCommon(c[0], n, e, 0);
                    if (c[0].newPos + 1 >= l && h + 1 >= f) return s([{value: this.join(n), count: n.length}]);

                    function p() {
                        for (var r = -1 * u; r <= u; r += 2) {
                            var o = void 0, d = c[r - 1], h = c[r + 1], p = (h ? h.newPos : 0) - r;
                            d && (c[r - 1] = void 0);
                            var b = d && d.newPos + 1 < l, g = h && 0 <= p && p < f;
                            if (b || g) {
                                if (!b || g && d.newPos < h.newPos ? (o = i(h), a.pushComponent(o.components, void 0, !0)) : ((o = d).newPos++, a.pushComponent(o.components, !0, void 0)), p = a.extractCommon(o, n, e, r), o.newPos + 1 >= l && p + 1 >= f) return s(t(a, o.components, n, e, a.useLongestToken));
                                c[r] = o
                            } else c[r] = void 0
                        }
                        u++
                    }

                    if (o) !function e() {
                        setTimeout((function () {
                            if (u > d) return o();
                            p() || e()
                        }), 0)
                    }(); else for (; u <= d;) {
                        var b = p();
                        if (b) return b
                    }
                }, pushComponent: function (e, n, t) {
                    var i = e[e.length - 1];
                    i && i.added === n && i.removed === t ? e[e.length - 1] = {count: i.count + 1, added: n, removed: t} : e.push({count: 1, added: n, removed: t})
                }, extractCommon: function (e, n, t, i) {
                    for (var r = n.length, o = t.length, a = e.newPos, s = a - i, l = 0; a + 1 < r && s + 1 < o && this.equals(n[a + 1], t[s + 1]);) a++, s++, l++;
                    return l && e.components.push({count: l}), e.newPos = a, s
                }, equals: function (e, n) {
                    return this.options.comparator ? this.options.comparator(e, n) : e === n || this.options.ignoreCase && e.toLowerCase() === n.toLowerCase()
                }, removeEmpty: function (e) {
                    for (var n = [], t = 0; t < e.length; t++) e[t] && n.push(e[t]);
                    return n
                }, castInput: function (e) {
                    return e
                }, tokenize: function (e) {
                    return e.split("")
                }, join: function (e) {
                    return e.join("")
                }
            };
            var r = new n;

            function o(e, n) {
                if ("function" == typeof e) n.callback = e; else if (e) for (var t in e) e.hasOwnProperty(t) && (n[t] = e[t]);
                return n
            }

            var a = /^[A-Za-z\xC0-\u02C6\u02C8-\u02D7\u02DE-\u02FF\u1E00-\u1EFF]+$/, s = /\S/, l = new n;
            l.equals = function (e, n) {
                return this.options.ignoreCase && (e = e.toLowerCase(), n = n.toLowerCase()), e === n || this.options.ignoreWhitespace && !s.test(e) && !s.test(n)
            }, l.tokenize = function (e) {
                for (var n = e.split(/(\s+|[()[\]{}'"]|\b)/), t = 0; t < n.length - 1; t++) !n[t + 1] && n[t + 2] && a.test(n[t]) && a.test(n[t + 2]) && (n[t] += n[t + 2], n.splice(t + 1, 2), t--);
                return n
            };
            var f = new n;

            function u(e, n, t) {
                return f.diff(e, n, t)
            }

            f.tokenize = function (e) {
                var n = [], t = e.split(/(\n|\r\n)/);
                t[t.length - 1] || t.pop();
                for (var i = 0; i < t.length; i++) {
                    var r = t[i];
                    i % 2 && !this.options.newlineIsToken ? n[n.length - 1] += r : (this.options.ignoreWhitespace && (r = r.trim()), n.push(r))
                }
                return n
            };
            var d = new n;
            d.tokenize = function (e) {
                return e.split(/(\S.+?[.!?])(?=\s+|$)/)
            };
            var c = new n;

            function h(e) {
                return (h = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (e) {
                    return typeof e
                } : function (e) {
                    return e && "function" == typeof Symbol && e.constructor === Symbol && e !== Symbol.prototype ? "symbol" : typeof e
                })(e)
            }

            function p(e) {
                return function (e) {
                    if (Array.isArray(e)) {
                        for (var n = 0, t = new Array(e.length); n < e.length; n++) t[n] = e[n];
                        return t
                    }
                }(e) || function (e) {
                    if (Symbol.iterator in Object(e) || "[object Arguments]" === Object.prototype.toString.call(e)) return Array.from(e)
                }(e) || function () {
                    throw new TypeError("Invalid attempt to spread non-iterable instance")
                }()
            }

            c.tokenize = function (e) {
                return e.split(/([{}:;,]|\s+)/)
            };
            var b = Object.prototype.toString, g = new n;

            function v(e, n, t, i, r) {
                var o, a;
                for (n = n || [], t = t || [], i && (e = i(r, e)), o = 0; o < n.length; o += 1) if (n[o] === e) return t[o];
                if ("[object Array]" === b.call(e)) {
                    for (n.push(e), a = new Array(e.length), t.push(a), o = 0; o < e.length; o += 1) a[o] = v(e[o], n, t, i, r);
                    return n.pop(), t.pop(), a
                }
                if (e && e.toJSON && (e = e.toJSON()), "object" === h(e) && null !== e) {
                    n.push(e), a = {}, t.push(a);
                    var s, l = [];
                    for (s in e) e.hasOwnProperty(s) && l.push(s);
                    for (l.sort(), o = 0; o < l.length; o += 1) a[s = l[o]] = v(e[s], n, t, i, s);
                    n.pop(), t.pop()
                } else a = e;
                return a
            }

            g.useLongestToken = !0, g.tokenize = f.tokenize, g.castInput = function (e) {
                var n = this.options, t = n.undefinedReplacement, i = n.stringifyReplacer, r = void 0 === i ? function (e, n) {
                    return void 0 === n ? t : n
                } : i;
                return "string" == typeof e ? e : JSON.stringify(v(e, null, null, r), r, "  ")
            }, g.equals = function (e, t) {
                return n.prototype.equals.call(g, e.replace(/,([\r\n])/g, "$1"), t.replace(/,([\r\n])/g, "$1"))
            };
            var m = new n;

            function y(e) {
                var n = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : {}, t = e.split(/\r\n|[\n\v\f\r\x85]/), i = e.match(/\r\n|[\n\v\f\r\x85]/g) || [], r = [],
                    o = 0;

                function a() {
                    var e = {};
                    for (r.push(e); o < t.length;) {
                        var i = t[o];
                        if (/^(\-\-\-|\+\+\+|@@)\s/.test(i)) break;
                        var a = /^(?:Index:|diff(?: -r \w+)+)\s+(.+?)\s*$/.exec(i);
                        a && (e.index = a[1]), o++
                    }
                    for (s(e), s(e), e.hunks = []; o < t.length;) {
                        var f = t[o];
                        if (/^(Index:|diff|\-\-\-|\+\+\+)\s/.test(f)) break;
                        if (/^@@/.test(f)) e.hunks.push(l()); else {
                            if (f && n.strict) throw new Error("Unknown line " + (o + 1) + " " + JSON.stringify(f));
                            o++
                        }
                    }
                }

                function s(e) {
                    var n = /^(---|\+\+\+)\s+(.*)$/.exec(t[o]);
                    if (n) {
                        var i = "---" === n[1] ? "old" : "new", r = n[2].split("\t", 2), a = r[0].replace(/\\\\/g, "\\");
                        /^".*"$/.test(a) && (a = a.substr(1, a.length - 2)), e[i + "FileName"] = a, e[i + "Header"] = (r[1] || "").trim(), o++
                    }
                }

                function l() {
                    for (var e = o, r = t[o++].split(/@@ -(\d+)(?:,(\d+))? \+(\d+)(?:,(\d+))? @@/), a = {
                        oldStart: +r[1],
                        oldLines: +r[2] || 1,
                        newStart: +r[3],
                        newLines: +r[4] || 1,
                        lines: [],
                        linedelimiters: []
                    }, s = 0, l = 0; o < t.length && !(0 === t[o].indexOf("--- ") && o + 2 < t.length && 0 === t[o + 1].indexOf("+++ ") && 0 === t[o + 2].indexOf("@@")); o++) {
                        var f = 0 == t[o].length && o != t.length - 1 ? " " : t[o][0];
                        if ("+" !== f && "-" !== f && " " !== f && "\\" !== f) break;
                        a.lines.push(t[o]), a.linedelimiters.push(i[o] || "\n"), "+" === f ? s++ : "-" === f ? l++ : " " === f && (s++, l++)
                    }
                    if (s || 1 !== a.newLines || (a.newLines = 0), l || 1 !== a.oldLines || (a.oldLines = 0), n.strict) {
                        if (s !== a.newLines) throw new Error("Added line count did not match for hunk at line " + (e + 1));
                        if (l !== a.oldLines) throw new Error("Removed line count did not match for hunk at line " + (e + 1))
                    }
                    return a
                }

                for (; o < t.length;) a();
                return r
            }

            function w(e, n, t) {
                var i = !0, r = !1, o = !1, a = 1;
                return function s() {
                    if (i && !o) {
                        if (r ? a++ : i = !1, e + a <= t) return a;
                        o = !0
                    }
                    if (!r) return o || (i = !0), n <= e - a ? -a++ : (r = !0, s())
                }
            }

            function S(e, n) {
                var t = arguments.length > 2 && void 0 !== arguments[2] ? arguments[2] : {};
                if ("string" == typeof n && (n = y(n)), Array.isArray(n)) {
                    if (n.length > 1) throw new Error("applyPatch only works with a single input.");
                    n = n[0]
                }
                var i, r, o = e.split(/\r\n|[\n\v\f\r\x85]/), a = e.match(/\r\n|[\n\v\f\r\x85]/g) || [], s = n.hunks, l = t.compareLine || function (e, n, t, i) {
                    return n === i
                }, f = 0, u = t.fuzzFactor || 0, d = 0, c = 0;

                function h(e, n) {
                    for (var t = 0; t < e.lines.length; t++) {
                        var i = e.lines[t], r = i.length > 0 ? i[0] : " ", a = i.length > 0 ? i.substr(1) : i;
                        if (" " === r || "-" === r) {
                            if (!l(n + 1, o[n], r, a) && ++f > u) return !1;
                            n++
                        }
                    }
                    return !0
                }

                for (var p = 0; p < s.length; p++) {
                    for (var b = s[p], g = o.length - b.oldLines, v = 0, m = c + b.oldStart - 1, S = w(m, d, g); void 0 !== v; v = S()) if (h(b, m + v)) {
                        b.offset = c += v;
                        break
                    }
                    if (void 0 === v) return !1;
                    d = b.offset + b.oldStart + b.oldLines
                }
                for (var L = 0, x = 0; x < s.length; x++) {
                    var C = s[x], T = C.oldStart + C.offset + L - 1;
                    L += C.newLines - C.oldLines, T < 0 && (T = 0);
                    for (var N = 0; N < C.lines.length; N++) {
                        var O = C.lines[N], _ = O.length > 0 ? O[0] : " ", E = O.length > 0 ? O.substr(1) : O, H = C.linedelimiters[N];
                        if (" " === _) T++; else if ("-" === _) o.splice(T, 1), a.splice(T, 1); else if ("+" === _) o.splice(T, 0, E), a.splice(T, 0, H), T++; else if ("\\" === _) {
                            var j = C.lines[N - 1] ? C.lines[N - 1][0] : null;
                            "+" === j ? i = !0 : "-" === j && (r = !0)
                        }
                    }
                }
                if (i) for (; !o[o.length - 1];) o.pop(), a.pop(); else r && (o.push(""), a.push("\n"));
                for (var k = 0; k < o.length - 1; k++) o[k] = o[k] + a[k];
                return o.join("")
            }

            function L(e, n, t, i, r, o, a) {
                a || (a = {}), void 0 === a.context && (a.context = 4);
                var s = u(t, i, a);

                function l(e) {
                    return e.map((function (e) {
                        return " " + e
                    }))
                }

                s.push({value: "", lines: []});
                for (var f = [], d = 0, c = 0, h = [], b = 1, g = 1, v = function (e) {
                    var n = s[e], r = n.lines || n.value.replace(/\n$/, "").split("\n");
                    if (n.lines = r, n.added || n.removed) {
                        var o;
                        if (!d) {
                            var u = s[e - 1];
                            d = b, c = g, u && (h = a.context > 0 ? l(u.lines.slice(-a.context)) : [], d -= h.length, c -= h.length)
                        }
                        (o = h).push.apply(o, p(r.map((function (e) {
                            return (n.added ? "+" : "-") + e
                        })))), n.added ? g += r.length : b += r.length
                    } else {
                        if (d) if (r.length <= 2 * a.context && e < s.length - 2) {
                            var v;
                            (v = h).push.apply(v, p(l(r)))
                        } else {
                            var m, y = Math.min(r.length, a.context);
                            (m = h).push.apply(m, p(l(r.slice(0, y))));
                            var w = {oldStart: d, oldLines: b - d + y, newStart: c, newLines: g - c + y, lines: h};
                            if (e >= s.length - 2 && r.length <= a.context) {
                                var S = /\n$/.test(t), L = /\n$/.test(i), x = 0 == r.length && h.length > w.oldLines;
                                !S && x && h.splice(w.oldLines, 0, "\\ No newline at end of file"), (S || x) && L || h.push("\\ No newline at end of file")
                            }
                            f.push(w), d = 0, c = 0, h = []
                        }
                        b += r.length, g += r.length
                    }
                }, m = 0; m < s.length; m++) v(m);
                return {oldFileName: e, newFileName: n, oldHeader: r, newHeader: o, hunks: f}
            }

            function x(e, n, t, i, r, o, a) {
                var s = L(e, n, t, i, r, o, a), l = [];
                e == n && l.push("Index: " + e), l.push("==================================================================="), l.push("--- " + s.oldFileName + (void 0 === s.oldHeader ? "" : "\t" + s.oldHeader)), l.push("+++ " + s.newFileName + (void 0 === s.newHeader ? "" : "\t" + s.newHeader));
                for (var f = 0; f < s.hunks.length; f++) {
                    var u = s.hunks[f];
                    l.push("@@ -" + u.oldStart + "," + u.oldLines + " +" + u.newStart + "," + u.newLines + " @@"), l.push.apply(l, u.lines)
                }
                return l.join("\n") + "\n"
            }

            function C(e, n) {
                if (n.length > e.length) return !1;
                for (var t = 0; t < n.length; t++) if (n[t] !== e[t]) return !1;
                return !0
            }

            function T(e) {
                var n = function e(n) {
                    var t = 0, i = 0;
                    return n.forEach((function (n) {
                        if ("string" != typeof n) {
                            var r = e(n.mine), o = e(n.theirs);
                            void 0 !== t && (r.oldLines === o.oldLines ? t += r.oldLines : t = void 0), void 0 !== i && (r.newLines === o.newLines ? i += r.newLines : i = void 0)
                        } else void 0 === i || "+" !== n[0] && " " !== n[0] || i++, void 0 === t || "-" !== n[0] && " " !== n[0] || t++
                    })), {oldLines: t, newLines: i}
                }(e.lines), t = n.oldLines, i = n.newLines;
                void 0 !== t ? e.oldLines = t : delete e.oldLines, void 0 !== i ? e.newLines = i : delete e.newLines
            }

            function N(e, n) {
                if ("string" == typeof e) {
                    if (/^@@/m.test(e) || /^Index:/m.test(e)) return y(e)[0];
                    if (!n) throw new Error("Must provide a base reference or pass in a patch");
                    return L(void 0, void 0, n, e)
                }
                return e
            }

            function O(e) {
                return e.newFileName && e.newFileName !== e.oldFileName
            }

            function _(e, n, t) {
                return n === t ? n : (e.conflict = !0, {mine: n, theirs: t})
            }

            function E(e, n) {
                return e.oldStart < n.oldStart && e.oldStart + e.oldLines < n.oldStart
            }

            function H(e, n) {
                return {oldStart: e.oldStart, oldLines: e.oldLines, newStart: e.newStart + n, newLines: e.newLines, lines: e.lines}
            }

            function j(e, n, t, i, r) {
                var o = {offset: n, lines: t, index: 0}, a = {offset: i, lines: r, index: 0};
                for (M(e, o, a), M(e, a, o); o.index < o.lines.length && a.index < a.lines.length;) {
                    var s = o.lines[o.index], l = a.lines[a.index];
                    if ("-" !== s[0] && "+" !== s[0] || "-" !== l[0] && "+" !== l[0]) if ("+" === s[0] && " " === l[0]) {
                        var f;
                        (f = e.lines).push.apply(f, p(D(o)))
                    } else if ("+" === l[0] && " " === s[0]) {
                        var u;
                        (u = e.lines).push.apply(u, p(D(a)))
                    } else "-" === s[0] && " " === l[0] ? F(e, o, a) : "-" === l[0] && " " === s[0] ? F(e, a, o, !0) : s === l ? (e.lines.push(s), o.index++, a.index++) : P(e, D(o), D(a)); else k(e, o, a)
                }
                I(e, o), I(e, a), T(e)
            }

            function k(e, n, t) {
                var i, r, o = D(n), a = D(t);
                if (A(o) && A(a)) {
                    var s, l;
                    if (C(o, a) && R(t, o, o.length - a.length)) return void (s = e.lines).push.apply(s, p(o));
                    if (C(a, o) && R(n, a, a.length - o.length)) return void (l = e.lines).push.apply(l, p(a))
                } else if (r = a, (i = o).length === r.length && C(i, r)) {
                    var f;
                    return void (f = e.lines).push.apply(f, p(o))
                }
                P(e, o, a)
            }

            function F(e, n, t, i) {
                var r, o = D(n), a = function (e, n) {
                    for (var t = [], i = [], r = 0, o = !1, a = !1; r < n.length && e.index < e.lines.length;) {
                        var s = e.lines[e.index], l = n[r];
                        if ("+" === l[0]) break;
                        if (o = o || " " !== s[0], i.push(l), r++, "+" === s[0]) for (a = !0; "+" === s[0];) t.push(s), s = e.lines[++e.index];
                        l.substr(1) === s.substr(1) ? (t.push(s), e.index++) : a = !0
                    }
                    if ("+" === (n[r] || "")[0] && o && (a = !0), a) return t;
                    for (; r < n.length;) i.push(n[r++]);
                    return {merged: i, changes: t}
                }(t, o);
                a.merged ? (r = e.lines).push.apply(r, p(a.merged)) : P(e, i ? a : o, i ? o : a)
            }

            function P(e, n, t) {
                e.conflict = !0, e.lines.push({conflict: !0, mine: n, theirs: t})
            }

            function M(e, n, t) {
                for (; n.offset < t.offset && n.index < n.lines.length;) {
                    var i = n.lines[n.index++];
                    e.lines.push(i), n.offset++
                }
            }

            function I(e, n) {
                for (; n.index < n.lines.length;) {
                    var t = n.lines[n.index++];
                    e.lines.push(t)
                }
            }

            function D(e) {
                for (var n = [], t = e.lines[e.index][0]; e.index < e.lines.length;) {
                    var i = e.lines[e.index];
                    if ("-" === t && "+" === i[0] && (t = "+"), t !== i[0]) break;
                    n.push(i), e.index++
                }
                return n
            }

            function A(e) {
                return e.reduce((function (e, n) {
                    return e && "-" === n[0]
                }), !0)
            }

            function R(e, n, t) {
                for (var i = 0; i < t; i++) {
                    var r = n[n.length - t + i].substr(1);
                    if (e.lines[e.index + i] !== " " + r) return !1
                }
                return e.index += t, !0
            }

            m.tokenize = function (e) {
                return e.slice()
            }, m.join = m.removeEmpty = function (e) {
                return e
            }, e.Diff = n, e.diffChars = function (e, n, t) {
                return r.diff(e, n, t)
            }, e.diffWords = function (e, n, t) {
                return t = o(t, {ignoreWhitespace: !0}), l.diff(e, n, t)
            }, e.diffWordsWithSpace = function (e, n, t) {
                return l.diff(e, n, t)
            }, e.diffLines = u, e.diffTrimmedLines = function (e, n, t) {
                var i = o(t, {ignoreWhitespace: !0});
                return f.diff(e, n, i)
            }, e.diffSentences = function (e, n, t) {
                return d.diff(e, n, t)
            }, e.diffCss = function (e, n, t) {
                return c.diff(e, n, t)
            }, e.diffJson = function (e, n, t) {
                return g.diff(e, n, t)
            }, e.diffArrays = function (e, n, t) {
                return m.diff(e, n, t)
            }, e.structuredPatch = L, e.createTwoFilesPatch = x, e.createPatch = function (e, n, t, i, r, o) {
                return x(e, e, n, t, i, r, o)
            }, e.applyPatch = S, e.applyPatches = function (e, n) {
                "string" == typeof e && (e = y(e));
                var t = 0;
                !function i() {
                    var r = e[t++];
                    if (!r) return n.complete();
                    n.loadFile(r, (function (e, t) {
                        if (e) return n.complete(e);
                        var o = S(t, r, n);
                        n.patched(r, o, (function (e) {
                            if (e) return n.complete(e);
                            i()
                        }))
                    }))
                }()
            }, e.parsePatch = y, e.merge = function (e, n, t) {
                e = N(e, t), n = N(n, t);
                var i = {};
                (e.index || n.index) && (i.index = e.index || n.index), (e.newFileName || n.newFileName) && (O(e) ? O(n) ? (i.oldFileName = _(i, e.oldFileName, n.oldFileName), i.newFileName = _(i, e.newFileName, n.newFileName), i.oldHeader = _(i, e.oldHeader, n.oldHeader), i.newHeader = _(i, e.newHeader, n.newHeader)) : (i.oldFileName = e.oldFileName, i.newFileName = e.newFileName, i.oldHeader = e.oldHeader, i.newHeader = e.newHeader) : (i.oldFileName = n.oldFileName || e.oldFileName, i.newFileName = n.newFileName || e.newFileName, i.oldHeader = n.oldHeader || e.oldHeader, i.newHeader = n.newHeader || e.newHeader)), i.hunks = [];
                for (var r = 0, o = 0, a = 0, s = 0; r < e.hunks.length || o < n.hunks.length;) {
                    var l = e.hunks[r] || {oldStart: 1 / 0}, f = n.hunks[o] || {oldStart: 1 / 0};
                    if (E(l, f)) i.hunks.push(H(l, a)), r++, s += l.newLines - l.oldLines; else if (E(f, l)) i.hunks.push(H(f, s)), o++, a += f.newLines - f.oldLines; else {
                        var u = {oldStart: Math.min(l.oldStart, f.oldStart), oldLines: 0, newStart: Math.min(l.newStart + a, f.oldStart + s), newLines: 0, lines: []};
                        j(u, l.oldStart, l.lines, f.oldStart, f.lines), o++, r++, i.hunks.push(u)
                    }
                }
                return i
            }, e.convertChangesToDMP = function (e) {
                for (var n, t, i = [], r = 0; r < e.length; r++) t = (n = e[r]).added ? 1 : n.removed ? -1 : 0, i.push([t, n.value]);
                return i
            }, e.convertChangesToXML = function (e) {
                for (var n = [], t = 0; t < e.length; t++) {
                    var i = e[t];
                    i.added ? n.push("<ins>") : i.removed && n.push("<del>"), n.push((r = i.value, void 0, r.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;"))), i.added ? n.push("</ins>") : i.removed && n.push("</del>")
                }
                var r;
                return n.join("")
            }, e.canonicalize = v, Object.defineProperty(e, "__esModule", {value: !0})
        }(n)
    }, function (e, n, t) {
        "use strict";
        var i = this && this.__assign || function () {
            return (i = Object.assign || function (e) {
                for (var n, t = 1, i = arguments.length; t < i; t++) for (var r in n = arguments[t]) Object.prototype.hasOwnProperty.call(n, r) && (e[r] = n[r]);
                return e
            }).apply(this, arguments)
        }, r = this && this.__createBinding || (Object.create ? function (e, n, t, i) {
            void 0 === i && (i = t), Object.defineProperty(e, i, {
                enumerable: !0, get: function () {
                    return n[t]
                }
            })
        } : function (e, n, t, i) {
            void 0 === i && (i = t), e[i] = n[t]
        }), o = this && this.__setModuleDefault || (Object.create ? function (e, n) {
            Object.defineProperty(e, "default", {enumerable: !0, value: n})
        } : function (e, n) {
            e.default = n
        }), a = this && this.__importStar || function (e) {
            if (e && e.__esModule) return e;
            var n = {};
            if (null != e) for (var t in e) "default" !== t && Object.prototype.hasOwnProperty.call(e, t) && r(n, e, t);
            return o(n, e), n
        };
        Object.defineProperty(n, "__esModule", {value: !0}), n.defaultLineByLineRendererConfig = void 0;
        var s = a(t(2)), l = a(t(1)), f = t(0);
        n.defaultLineByLineRendererConfig = i(i({}, l.defaultRenderConfig), {renderNothingWhenEmpty: !1, matchingMaxComparisons: 2500, maxLineSizeInBlockForComparison: 200});
        var u = function () {
            function e(e, t) {
                void 0 === t && (t = {}), this.hoganUtils = e, this.config = i(i({}, n.defaultLineByLineRendererConfig), t)
            }

            return e.prototype.render = function (e) {
                var n = this, t = e.map((function (e) {
                    var t;
                    return t = e.blocks.length ? n.generateFileHtml(e) : n.generateEmptyDiff(), n.makeFileDiffHtml(e, t)
                })).join("\n");
                return this.hoganUtils.render("generic", "wrapper", {content: t})
            }, e.prototype.makeFileDiffHtml = function (e, n) {
                if (this.config.renderNothingWhenEmpty && Array.isArray(e.blocks) && 0 === e.blocks.length) return "";
                var t = this.hoganUtils.template("line-by-line", "file-diff"), i = this.hoganUtils.template("generic", "file-path"), r = this.hoganUtils.template("icon", "file"),
                    o = this.hoganUtils.template("tag", l.getFileIcon(e));
                return t.render({file: e, fileHtmlId: l.getHtmlId(e), diffs: n, filePath: i.render({fileDiffName: l.filenameDiff(e)}, {fileIcon: r, fileTag: o})})
            }, e.prototype.generateEmptyDiff = function () {
                return this.hoganUtils.render("generic", "empty-diff", {contentClass: "d2h-code-line", CSSLineClass: l.CSSLineClass})
            }, e.prototype.generateFileHtml = function (e) {
                var n = this, t = s.newMatcherFn(s.newDistanceFn((function (n) {
                    return l.deconstructLine(n.content, e.isCombined).content
                })));
                return e.blocks.map((function (i) {
                    var r = n.hoganUtils.render("generic", "block-header", {
                        CSSLineClass: l.CSSLineClass,
                        blockHeader: l.escapeForHtml(i.header),
                        lineClass: "d2h-code-linenumber",
                        contentClass: "d2h-code-line"
                    });
                    return n.applyLineGroupping(i).forEach((function (i) {
                        var o = i[0], a = i[1], s = i[2];
                        if (a.length && s.length && !o.length) n.applyRematchMatching(a, s, t).map((function (t) {
                            var i = t[0], o = t[1], a = n.processChangedLines(e.isCombined, i, o), s = a.left, l = a.right;
                            r += s, r += l
                        })); else if (o.length) o.forEach((function (t) {
                            var i = l.deconstructLine(t.content, e.isCombined), o = i.prefix, a = i.content;
                            r += n.generateSingleLineHtml({type: l.CSSLineClass.CONTEXT, prefix: o, content: a, oldNumber: t.oldNumber, newNumber: t.newNumber})
                        })); else if (a.length || s.length) {
                            var f = n.processChangedLines(e.isCombined, a, s), u = f.left, d = f.right;
                            r += u, r += d
                        } else console.error("Unknown state reached while processing groups of lines", o, a, s)
                    })), r
                })).join("\n")
            }, e.prototype.applyLineGroupping = function (e) {
                for (var n = [], t = [], i = [], r = 0; r < e.lines.length; r++) {
                    var o = e.lines[r];
                    (o.type !== f.LineType.INSERT && i.length || o.type === f.LineType.CONTEXT && t.length > 0) && (n.push([[], t, i]), t = [], i = []), o.type === f.LineType.CONTEXT ? n.push([[o], [], []]) : o.type === f.LineType.INSERT && 0 === t.length ? n.push([[], [], [o]]) : o.type === f.LineType.INSERT && t.length > 0 ? i.push(o) : o.type === f.LineType.DELETE && t.push(o)
                }
                return (t.length || i.length) && (n.push([[], t, i]), t = [], i = []), n
            }, e.prototype.applyRematchMatching = function (e, n, t) {
                var i = e.length * n.length, r = Math.max.apply(null, [0].concat(e.concat(n).map((function (e) {
                    return e.content.length
                }))));
                return i < this.config.matchingMaxComparisons && r < this.config.maxLineSizeInBlockForComparison && ("lines" === this.config.matching || "words" === this.config.matching) ? t(e, n) : [[e, n]]
            }, e.prototype.processChangedLines = function (e, n, t) {
                for (var r = {right: "", left: ""}, o = Math.max(n.length, t.length), a = 0; a < o; a++) {
                    var s = n[a], f = t[a], u = void 0 !== s && void 0 !== f ? l.diffHighlight(s.content, f.content, e, this.config) : void 0,
                        d = void 0 !== s && void 0 !== s.oldNumber ? i(i({}, void 0 !== u ? {
                            prefix: u.oldLine.prefix,
                            content: u.oldLine.content,
                            type: l.CSSLineClass.DELETE_CHANGES
                        } : i(i({}, l.deconstructLine(s.content, e)), {type: l.toCSSClass(s.type)})), {oldNumber: s.oldNumber, newNumber: s.newNumber}) : void 0,
                        c = void 0 !== f && void 0 !== f.newNumber ? i(i({}, void 0 !== u ? {
                            prefix: u.newLine.prefix,
                            content: u.newLine.content,
                            type: l.CSSLineClass.INSERT_CHANGES
                        } : i(i({}, l.deconstructLine(f.content, e)), {type: l.toCSSClass(f.type)})), {oldNumber: f.oldNumber, newNumber: f.newNumber}) : void 0,
                        h = this.generateLineHtml(d, c), p = h.left, b = h.right;
                    r.left += p, r.right += b
                }
                return r
            }, e.prototype.generateLineHtml = function (e, n) {
                return {left: this.generateSingleLineHtml(e), right: this.generateSingleLineHtml(n)}
            }, e.prototype.generateSingleLineHtml = function (e) {
                if (void 0 === e) return "";
                var n = this.hoganUtils.render("line-by-line", "numbers", {oldNumber: e.oldNumber || "", newNumber: e.newNumber || ""});
                return this.hoganUtils.render("generic", "line", {
                    type: e.type,
                    lineClass: "d2h-code-linenumber",
                    contentClass: "d2h-code-line",
                    prefix: " " === e.prefix ? "&nbsp;" : e.prefix,
                    content: e.content,
                    lineNumber: n
                })
            }, e
        }();
        n.default = u
    }, function (e, n, t) {
        "use strict";
        var i = this && this.__assign || function () {
            return (i = Object.assign || function (e) {
                for (var n, t = 1, i = arguments.length; t < i; t++) for (var r in n = arguments[t]) Object.prototype.hasOwnProperty.call(n, r) && (e[r] = n[r]);
                return e
            }).apply(this, arguments)
        }, r = this && this.__createBinding || (Object.create ? function (e, n, t, i) {
            void 0 === i && (i = t), Object.defineProperty(e, i, {
                enumerable: !0, get: function () {
                    return n[t]
                }
            })
        } : function (e, n, t, i) {
            void 0 === i && (i = t), e[i] = n[t]
        }), o = this && this.__setModuleDefault || (Object.create ? function (e, n) {
            Object.defineProperty(e, "default", {enumerable: !0, value: n})
        } : function (e, n) {
            e.default = n
        }), a = this && this.__importStar || function (e) {
            if (e && e.__esModule) return e;
            var n = {};
            if (null != e) for (var t in e) "default" !== t && Object.prototype.hasOwnProperty.call(e, t) && r(n, e, t);
            return o(n, e), n
        };
        Object.defineProperty(n, "__esModule", {value: !0}), n.defaultSideBySideRendererConfig = void 0;
        var s = a(t(2)), l = a(t(1)), f = t(0);
        n.defaultSideBySideRendererConfig = i(i({}, l.defaultRenderConfig), {renderNothingWhenEmpty: !1, matchingMaxComparisons: 2500, maxLineSizeInBlockForComparison: 200});
        var u = function () {
            function e(e, t) {
                void 0 === t && (t = {}), this.hoganUtils = e, this.config = i(i({}, n.defaultSideBySideRendererConfig), t)
            }

            return e.prototype.render = function (e) {
                var n = this, t = e.map((function (e) {
                    var t;
                    return t = e.blocks.length ? n.generateFileHtml(e) : n.generateEmptyDiff(), n.makeFileDiffHtml(e, t)
                })).join("\n");
                return this.hoganUtils.render("generic", "wrapper", {content: t})
            }, e.prototype.makeFileDiffHtml = function (e, n) {
                if (this.config.renderNothingWhenEmpty && Array.isArray(e.blocks) && 0 === e.blocks.length) return "";
                var t = this.hoganUtils.template("side-by-side", "file-diff"), i = this.hoganUtils.template("generic", "file-path"), r = this.hoganUtils.template("icon", "file"),
                    o = this.hoganUtils.template("tag", l.getFileIcon(e));
                return t.render({file: e, fileHtmlId: l.getHtmlId(e), diffs: n, filePath: i.render({fileDiffName: l.filenameDiff(e)}, {fileIcon: r, fileTag: o})})
            }, e.prototype.generateEmptyDiff = function () {
                return {right: "", left: this.hoganUtils.render("generic", "empty-diff", {contentClass: "d2h-code-side-line", CSSLineClass: l.CSSLineClass})}
            }, e.prototype.generateFileHtml = function (e) {
                var n = this, t = s.newMatcherFn(s.newDistanceFn((function (n) {
                    return l.deconstructLine(n.content, e.isCombined).content
                })));
                return e.blocks.map((function (i) {
                    var r = {left: n.makeHeaderHtml(i.header), right: n.makeHeaderHtml("")};
                    return n.applyLineGroupping(i).forEach((function (i) {
                        var o = i[0], a = i[1], s = i[2];
                        if (a.length && s.length && !o.length) n.applyRematchMatching(a, s, t).map((function (t) {
                            var i = t[0], o = t[1], a = n.processChangedLines(e.isCombined, i, o), s = a.left, l = a.right;
                            r.left += s, r.right += l
                        })); else if (o.length) o.forEach((function (t) {
                            var i = l.deconstructLine(t.content, e.isCombined), o = i.prefix, a = i.content,
                                s = n.generateLineHtml({type: l.CSSLineClass.CONTEXT, prefix: o, content: a, number: t.oldNumber}, {
                                    type: l.CSSLineClass.CONTEXT,
                                    prefix: o,
                                    content: a,
                                    number: t.newNumber
                                }), f = s.left, u = s.right;
                            r.left += f, r.right += u
                        })); else if (a.length || s.length) {
                            var f = n.processChangedLines(e.isCombined, a, s), u = f.left, d = f.right;
                            r.left += u, r.right += d
                        } else console.error("Unknown state reached while processing groups of lines", o, a, s)
                    })), r
                })).reduce((function (e, n) {
                    return {left: e.left + n.left, right: e.right + n.right}
                }), {left: "", right: ""})
            }, e.prototype.applyLineGroupping = function (e) {
                for (var n = [], t = [], i = [], r = 0; r < e.lines.length; r++) {
                    var o = e.lines[r];
                    (o.type !== f.LineType.INSERT && i.length || o.type === f.LineType.CONTEXT && t.length > 0) && (n.push([[], t, i]), t = [], i = []), o.type === f.LineType.CONTEXT ? n.push([[o], [], []]) : o.type === f.LineType.INSERT && 0 === t.length ? n.push([[], [], [o]]) : o.type === f.LineType.INSERT && t.length > 0 ? i.push(o) : o.type === f.LineType.DELETE && t.push(o)
                }
                return (t.length || i.length) && (n.push([[], t, i]), t = [], i = []), n
            }, e.prototype.applyRematchMatching = function (e, n, t) {
                var i = e.length * n.length, r = Math.max.apply(null, [0].concat(e.concat(n).map((function (e) {
                    return e.content.length
                }))));
                return i < this.config.matchingMaxComparisons && r < this.config.maxLineSizeInBlockForComparison && ("lines" === this.config.matching || "words" === this.config.matching) ? t(e, n) : [[e, n]]
            }, e.prototype.makeHeaderHtml = function (e) {
                return this.hoganUtils.render("generic", "block-header", {
                    CSSLineClass: l.CSSLineClass,
                    blockHeader: l.escapeForHtml(e),
                    lineClass: "d2h-code-side-linenumber",
                    contentClass: "d2h-code-side-line"
                })
            }, e.prototype.processChangedLines = function (e, n, t) {
                for (var r = {right: "", left: ""}, o = Math.max(n.length, t.length), a = 0; a < o; a++) {
                    var s = n[a], f = t[a], u = void 0 !== s && void 0 !== f ? l.diffHighlight(s.content, f.content, e, this.config) : void 0,
                        d = void 0 !== s && void 0 !== s.oldNumber ? i(i({}, void 0 !== u ? {
                            prefix: u.oldLine.prefix,
                            content: u.oldLine.content,
                            type: l.CSSLineClass.DELETE_CHANGES
                        } : i(i({}, l.deconstructLine(s.content, e)), {type: l.toCSSClass(s.type)})), {number: s.oldNumber}) : void 0,
                        c = void 0 !== f && void 0 !== f.newNumber ? i(i({}, void 0 !== u ? {
                            prefix: u.newLine.prefix,
                            content: u.newLine.content,
                            type: l.CSSLineClass.INSERT_CHANGES
                        } : i(i({}, l.deconstructLine(f.content, e)), {type: l.toCSSClass(f.type)})), {number: f.newNumber}) : void 0, h = this.generateLineHtml(d, c), p = h.left,
                        b = h.right;
                    r.left += p, r.right += b
                }
                return r
            }, e.prototype.generateLineHtml = function (e, n) {
                return {left: this.generateSingleHtml(e), right: this.generateSingleHtml(n)}
            }, e.prototype.generateSingleHtml = function (e) {
                return this.hoganUtils.render("generic", "line", {
                    type: (null == e ? void 0 : e.type) || l.CSSLineClass.CONTEXT + " d2h-emptyplaceholder",
                    lineClass: void 0 !== e ? "d2h-code-side-linenumber" : "d2h-code-side-linenumber d2h-code-side-emptyplaceholder",
                    contentClass: void 0 !== e ? "d2h-code-side-line" : "d2h-code-side-line d2h-code-side-emptyplaceholder",
                    prefix: " " === (null == e ? void 0 : e.prefix) ? "&nbsp;" : null == e ? void 0 : e.prefix,
                    content: null == e ? void 0 : e.content,
                    lineNumber: null == e ? void 0 : e.number
                })
            }, e
        }();
        n.default = u
    }, function (e, n, t) {
        "use strict";
        var i = this && this.__assign || function () {
            return (i = Object.assign || function (e) {
                for (var n, t = 1, i = arguments.length; t < i; t++) for (var r in n = arguments[t]) Object.prototype.hasOwnProperty.call(n, r) && (e[r] = n[r]);
                return e
            }).apply(this, arguments)
        }, r = this && this.__createBinding || (Object.create ? function (e, n, t, i) {
            void 0 === i && (i = t), Object.defineProperty(e, i, {
                enumerable: !0, get: function () {
                    return n[t]
                }
            })
        } : function (e, n, t, i) {
            void 0 === i && (i = t), e[i] = n[t]
        }), o = this && this.__setModuleDefault || (Object.create ? function (e, n) {
            Object.defineProperty(e, "default", {enumerable: !0, value: n})
        } : function (e, n) {
            e.default = n
        }), a = this && this.__importStar || function (e) {
            if (e && e.__esModule) return e;
            var n = {};
            if (null != e) for (var t in e) "default" !== t && Object.prototype.hasOwnProperty.call(e, t) && r(n, e, t);
            return o(n, e), n
        };
        Object.defineProperty(n, "__esModule", {value: !0});
        var s = a(t(4)), l = t(14), f = function () {
            function e(e) {
                var n = e.compiledTemplates, t = void 0 === n ? {} : n, r = e.rawTemplates, o = void 0 === r ? {} : r, a = Object.entries(o).reduce((function (e, n) {
                    var t, r = n[0], o = n[1], a = s.compile(o, {asString: !1});
                    return i(i({}, e), ((t = {})[r] = a, t))
                }), {});
                this.preCompiledTemplates = i(i(i({}, l.defaultTemplates), t), a)
            }

            return e.compile = function (e) {
                return s.compile(e, {asString: !1})
            }, e.prototype.render = function (e, n, t, i, r) {
                var o = this.templateKey(e, n);
                try {
                    return this.preCompiledTemplates[o].render(t, i, r)
                } catch (e) {
                    throw new Error("Could not find template to render '" + o + "'")
                }
            }, e.prototype.template = function (e, n) {
                return this.preCompiledTemplates[this.templateKey(e, n)]
            }, e.prototype.templateKey = function (e, n) {
                return e + "-" + n
            }, e
        }();
        n.default = f
    }, function (e, n, t) {
        !function (e) {
            var n = /\S/, t = /\"/g, i = /\n/g, r = /\r/g, o = /\\/g, a = /\u2028/, s = /\u2029/;

            function l(e) {
                "}" === e.n.substr(e.n.length - 1) && (e.n = e.n.substring(0, e.n.length - 1))
            }

            function f(e) {
                return e.trim ? e.trim() : e.replace(/^\s*|\s*$/g, "")
            }

            function u(e, n, t) {
                if (n.charAt(t) != e.charAt(0)) return !1;
                for (var i = 1, r = e.length; i < r; i++) if (n.charAt(t + i) != e.charAt(i)) return !1;
                return !0
            }

            e.tags = {"#": 1, "^": 2, "<": 3, $: 4, "/": 5, "!": 6, ">": 7, "=": 8, _v: 9, "{": 10, "&": 11, _t: 12}, e.scan = function (t, i) {
                var r = t.length, o = 0, a = null, s = null, d = "", c = [], h = !1, p = 0, b = 0, g = "{{", v = "}}";

                function m() {
                    d.length > 0 && (c.push({tag: "_t", text: new String(d)}), d = "")
                }

                function y(t, i) {
                    if (m(), t && function () {
                        for (var t = !0, i = b; i < c.length; i++) if (!(t = e.tags[c[i].tag] < e.tags._v || "_t" == c[i].tag && null === c[i].text.match(n))) return !1;
                        return t
                    }()) for (var r, o = b; o < c.length; o++) c[o].text && ((r = c[o + 1]) && ">" == r.tag && (r.indent = c[o].text.toString()), c.splice(o, 1)); else i || c.push({tag: "\n"});
                    h = !1, b = c.length
                }

                function w(e, n) {
                    var t = "=" + v, i = e.indexOf(t, n), r = f(e.substring(e.indexOf("=", n) + 1, i)).split(" ");
                    return g = r[0], v = r[r.length - 1], i + t.length - 1
                }

                for (i && (i = i.split(" "), g = i[0], v = i[1]), p = 0; p < r; p++) 0 == o ? u(g, t, p) ? (--p, m(), o = 1) : "\n" == t.charAt(p) ? y(h) : d += t.charAt(p) : 1 == o ? (p += g.length - 1, "=" == (a = (s = e.tags[t.charAt(p + 1)]) ? t.charAt(p + 1) : "_v") ? (p = w(t, p), o = 0) : (s && p++, o = 2), h = p) : u(v, t, p) ? (c.push({
                    tag: a,
                    n: f(d),
                    otag: g,
                    ctag: v,
                    i: "/" == a ? h - g.length : p + v.length
                }), d = "", p += v.length - 1, o = 0, "{" == a && ("}}" == v ? p++ : l(c[c.length - 1]))) : d += t.charAt(p);
                return y(h, !0), c
            };
            var d = {_t: !0, "\n": !0, $: !0, "/": !0};

            function c(e, n) {
                for (var t = 0, i = n.length; t < i; t++) if (n[t].o == e.n) return e.tag = "#", !0
            }

            function h(e, n, t) {
                for (var i = 0, r = t.length; i < r; i++) if (t[i].c == e && t[i].o == n) return !0
            }

            function p(e) {
                var n = [];
                for (var t in e.partials) n.push('"' + g(t) + '":{name:"' + g(e.partials[t].name) + '", ' + p(e.partials[t]) + "}");
                return "partials: {" + n.join(",") + "}, subs: " + function (e) {
                    var n = [];
                    for (var t in e) n.push('"' + g(t) + '": function(c,p,t,i) {' + e[t] + "}");
                    return "{ " + n.join(",") + " }"
                }(e.subs)
            }

            e.stringify = function (n, t, i) {
                return "{code: function (c,p,i) { " + e.wrapMain(n.code) + " }," + p(n) + "}"
            };
            var b = 0;

            function g(e) {
                return e.replace(o, "\\\\").replace(t, '\\"').replace(i, "\\n").replace(r, "\\r").replace(a, "\\u2028").replace(s, "\\u2029")
            }

            function v(e) {
                return ~e.indexOf(".") ? "d" : "f"
            }

            function m(e, n) {
                var t = "<" + (n.prefix || "") + e.n + b++;
                return n.partials[t] = {name: e.n, partials: {}}, n.code += 't.b(t.rp("' + g(t) + '",c,p,"' + (e.indent || "") + '"));', t
            }

            function y(e, n) {
                n.code += "t.b(t.t(t." + v(e.n) + '("' + g(e.n) + '",c,p,0)));'
            }

            function w(e) {
                return "t.b(" + e + ");"
            }

            e.generate = function (n, t, i) {
                b = 0;
                var r = {code: "", subs: {}, partials: {}};
                return e.walk(n, r), i.asString ? this.stringify(r, t, i) : this.makeTemplate(r, t, i)
            }, e.wrapMain = function (e) {
                return 'var t=this;t.b(i=i||"");' + e + "return t.fl();"
            }, e.template = e.Template, e.makeTemplate = function (e, n, t) {
                var i = this.makePartials(e);
                return i.code = new Function("c", "p", "i", this.wrapMain(e.code)), new this.template(i, n, this, t)
            }, e.makePartials = function (e) {
                var n, t = {subs: {}, partials: e.partials, name: e.name};
                for (n in t.partials) t.partials[n] = this.makePartials(t.partials[n]);
                for (n in e.subs) t.subs[n] = new Function("c", "p", "t", "i", e.subs[n]);
                return t
            }, e.codegen = {
                "#": function (n, t) {
                    t.code += "if(t.s(t." + v(n.n) + '("' + g(n.n) + '",c,p,1),c,p,0,' + n.i + "," + n.end + ',"' + n.otag + " " + n.ctag + '")){t.rs(c,p,function(c,p,t){', e.walk(n.nodes, t), t.code += "});c.pop();}"
                }, "^": function (n, t) {
                    t.code += "if(!t.s(t." + v(n.n) + '("' + g(n.n) + '",c,p,1),c,p,1,0,0,"")){', e.walk(n.nodes, t), t.code += "};"
                }, ">": m, "<": function (n, t) {
                    var i = {partials: {}, code: "", subs: {}, inPartial: !0};
                    e.walk(n.nodes, i);
                    var r = t.partials[m(n, t)];
                    r.subs = i.subs, r.partials = i.partials
                }, $: function (n, t) {
                    var i = {subs: {}, code: "", partials: t.partials, prefix: n.n};
                    e.walk(n.nodes, i), t.subs[n.n] = i.code, t.inPartial || (t.code += 't.sub("' + g(n.n) + '",c,p,i);')
                }, "\n": function (e, n) {
                    n.code += w('"\\n"' + (e.last ? "" : " + i"))
                }, _v: function (e, n) {
                    n.code += "t.b(t.v(t." + v(e.n) + '("' + g(e.n) + '",c,p,0)));'
                }, _t: function (e, n) {
                    n.code += w('"' + g(e.text) + '"')
                }, "{": y, "&": y
            }, e.walk = function (n, t) {
                for (var i, r = 0, o = n.length; r < o; r++) (i = e.codegen[n[r].tag]) && i(n[r], t);
                return t
            }, e.parse = function (n, t, i) {
                return function n(t, i, r, o) {
                    var a, s = [], l = null, f = null;
                    for (a = r[r.length - 1]; t.length > 0;) {
                        if (f = t.shift(), a && "<" == a.tag && !(f.tag in d)) throw new Error("Illegal content in < super tag.");
                        if (e.tags[f.tag] <= e.tags.$ || c(f, o)) r.push(f), f.nodes = n(t, f.tag, r, o); else {
                            if ("/" == f.tag) {
                                if (0 === r.length) throw new Error("Closing tag without opener: /" + f.n);
                                if (l = r.pop(), f.n != l.n && !h(f.n, l.n, o)) throw new Error("Nesting error: " + l.n + " vs. " + f.n);
                                return l.end = f.i, s
                            }
                            "\n" == f.tag && (f.last = 0 == t.length || "\n" == t[0].tag)
                        }
                        s.push(f)
                    }
                    if (r.length > 0) throw new Error("missing closing tag: " + r.pop().n);
                    return s
                }(n, 0, [], (i = i || {}).sectionTags || [])
            }, e.cache = {}, e.cacheKey = function (e, n) {
                return [e, !!n.asString, !!n.disableLambda, n.delimiters, !!n.modelGet].join("||")
            }, e.compile = function (n, t) {
                t = t || {};
                var i = e.cacheKey(n, t), r = this.cache[i];
                if (r) {
                    var o = r.partials;
                    for (var a in o) delete o[a].instance;
                    return r
                }
                return r = this.generate(this.parse(this.scan(n, t.delimiters), n, t), n, t), this.cache[i] = r
            }
        }(n)
    }, function (e, n, t) {
        !function (e) {
            function n(e, n, t) {
                var i;
                return n && "object" == typeof n && (void 0 !== n[e] ? i = n[e] : t && n.get && "function" == typeof n.get && (i = n.get(e))), i
            }

            e.Template = function (e, n, t, i) {
                e = e || {}, this.r = e.code || this.r, this.c = t, this.options = i || {}, this.text = n || "", this.partials = e.partials || {}, this.subs = e.subs || {}, this.buf = ""
            }, e.Template.prototype = {
                r: function (e, n, t) {
                    return ""
                }, v: function (e) {
                    return e = l(e), s.test(e) ? e.replace(t, "&amp;").replace(i, "&lt;").replace(r, "&gt;").replace(o, "&#39;").replace(a, "&quot;") : e
                }, t: l, render: function (e, n, t) {
                    return this.ri([e], n || {}, t)
                }, ri: function (e, n, t) {
                    return this.r(e, n, t)
                }, ep: function (e, n) {
                    var t = this.partials[e], i = n[t.name];
                    if (t.instance && t.base == i) return t.instance;
                    if ("string" == typeof i) {
                        if (!this.c) throw new Error("No compiler available.");
                        i = this.c.compile(i, this.options)
                    }
                    if (!i) return null;
                    if (this.partials[e].base = i, t.subs) {
                        for (key in n.stackText || (n.stackText = {}), t.subs) n.stackText[key] || (n.stackText[key] = void 0 !== this.activeSub && n.stackText[this.activeSub] ? n.stackText[this.activeSub] : this.text);
                        i = function (e, n, t, i, r, o) {
                            function a() {
                            }

                            function s() {
                            }

                            var l;
                            a.prototype = e, s.prototype = e.subs;
                            var f = new a;
                            for (l in f.subs = new s, f.subsText = {}, f.buf = "", i = i || {}, f.stackSubs = i, f.subsText = o, n) i[l] || (i[l] = n[l]);
                            for (l in i) f.subs[l] = i[l];
                            for (l in r = r || {}, f.stackPartials = r, t) r[l] || (r[l] = t[l]);
                            for (l in r) f.partials[l] = r[l];
                            return f
                        }(i, t.subs, t.partials, this.stackSubs, this.stackPartials, n.stackText)
                    }
                    return this.partials[e].instance = i, i
                }, rp: function (e, n, t, i) {
                    var r = this.ep(e, t);
                    return r ? r.ri(n, t, i) : ""
                }, rs: function (e, n, t) {
                    var i = e[e.length - 1];
                    if (f(i)) for (var r = 0; r < i.length; r++) e.push(i[r]), t(e, n, this), e.pop(); else t(e, n, this)
                }, s: function (e, n, t, i, r, o, a) {
                    var s;
                    return (!f(e) || 0 !== e.length) && ("function" == typeof e && (e = this.ms(e, n, t, i, r, o, a)), s = !!e, !i && s && n && n.push("object" == typeof e ? e : n[n.length - 1]), s)
                }, d: function (e, t, i, r) {
                    var o, a = e.split("."), s = this.f(a[0], t, i, r), l = this.options.modelGet, u = null;
                    if ("." === e && f(t[t.length - 2])) s = t[t.length - 1]; else for (var d = 1; d < a.length; d++) void 0 !== (o = n(a[d], s, l)) ? (u = s, s = o) : s = "";
                    return !(r && !s) && (r || "function" != typeof s || (t.push(u), s = this.mv(s, t, i), t.pop()), s)
                }, f: function (e, t, i, r) {
                    for (var o = !1, a = !1, s = this.options.modelGet, l = t.length - 1; l >= 0; l--) if (void 0 !== (o = n(e, t[l], s))) {
                        a = !0;
                        break
                    }
                    return a ? (r || "function" != typeof o || (o = this.mv(o, t, i)), o) : !r && ""
                }, ls: function (e, n, t, i, r) {
                    var o = this.options.delimiters;
                    return this.options.delimiters = r, this.b(this.ct(l(e.call(n, i)), n, t)), this.options.delimiters = o, !1
                }, ct: function (e, n, t) {
                    if (this.options.disableLambda) throw new Error("Lambda features disabled.");
                    return this.c.compile(e, this.options).render(n, t)
                }, b: function (e) {
                    this.buf += e
                }, fl: function () {
                    var e = this.buf;
                    return this.buf = "", e
                }, ms: function (e, n, t, i, r, o, a) {
                    var s, l = n[n.length - 1], f = e.call(l);
                    return "function" == typeof f ? !!i || (s = this.activeSub && this.subsText && this.subsText[this.activeSub] ? this.subsText[this.activeSub] : this.text, this.ls(f, l, t, s.substring(r, o), a)) : f
                }, mv: function (e, n, t) {
                    var i = n[n.length - 1], r = e.call(i);
                    return "function" == typeof r ? this.ct(l(r.call(i)), i, t) : r
                }, sub: function (e, n, t, i) {
                    var r = this.subs[e];
                    r && (this.activeSub = e, r(n, t, this, i), this.activeSub = !1)
                }
            };
            var t = /&/g, i = /</g, r = />/g, o = /\'/g, a = /\"/g, s = /[&<>\"\']/;

            function l(e) {
                return String(null == e ? "" : e)
            }

            var f = Array.isArray || function (e) {
                return "[object Array]" === Object.prototype.toString.call(e)
            }
        }(n)
    }, function (e, n, t) {
        "use strict";
        var i = this && this.__createBinding || (Object.create ? function (e, n, t, i) {
            void 0 === i && (i = t), Object.defineProperty(e, i, {
                enumerable: !0, get: function () {
                    return n[t]
                }
            })
        } : function (e, n, t, i) {
            void 0 === i && (i = t), e[i] = n[t]
        }), r = this && this.__setModuleDefault || (Object.create ? function (e, n) {
            Object.defineProperty(e, "default", {enumerable: !0, value: n})
        } : function (e, n) {
            e.default = n
        }), o = this && this.__importStar || function (e) {
            if (e && e.__esModule) return e;
            var n = {};
            if (null != e) for (var t in e) "default" !== t && Object.prototype.hasOwnProperty.call(e, t) && i(n, e, t);
            return r(n, e), n
        };
        Object.defineProperty(n, "__esModule", {value: !0}), n.defaultTemplates = void 0;
        var a = o(t(4));
        n.defaultTemplates = {}, n.defaultTemplates["file-summary-line"] = new a.Template({
            code: function (e, n, t) {
                var i = this;
                return i.b(t = t || ""), i.b('<li class="d2h-file-list-line">'), i.b("\n" + t), i.b('    <span class="d2h-file-name-wrapper">'), i.b("\n" + t), i.b(i.rp("<fileIcon0", e, n, "      ")), i.b('      <a href="#'), i.b(i.v(i.f("fileHtmlId", e, n, 0))), i.b('" class="d2h-file-name">'), i.b(i.v(i.f("fileName", e, n, 0))), i.b("</a>"), i.b("\n" + t), i.b('      <span class="d2h-file-stats">'), i.b("\n" + t), i.b('          <span class="d2h-lines-added">'), i.b(i.v(i.f("addedLines", e, n, 0))), i.b("</span>"), i.b("\n" + t), i.b('          <span class="d2h-lines-deleted">'), i.b(i.v(i.f("deletedLines", e, n, 0))), i.b("</span>"), i.b("\n" + t), i.b("      </span>"), i.b("\n" + t), i.b("    </span>"), i.b("\n" + t), i.b("</li>"), i.fl()
            }, partials: {"<fileIcon0": {name: "fileIcon", partials: {}, subs: {}}}, subs: {}
        }), n.defaultTemplates["file-summary-wrapper"] = new a.Template({
            code: function (e, n, t) {
                var i = this;
                return i.b(t = t || ""), i.b('<div class="d2h-file-list-wrapper">'), i.b("\n" + t), i.b('    <div class="d2h-file-list-header">'), i.b("\n" + t), i.b('        <span class="d2h-file-list-title">Files changed ('), i.b(i.v(i.f("filesNumber", e, n, 0))), i.b(")</span>"), i.b("\n" + t), i.b('        <a class="d2h-file-switch d2h-hide">hide</a>'), i.b("\n" + t), i.b('        <a class="d2h-file-switch d2h-show">show</a>'), i.b("\n" + t), i.b("    </div>"), i.b("\n" + t), i.b('    <ol class="d2h-file-list">'), i.b("\n" + t), i.b("    "), i.b(i.t(i.f("files", e, n, 0))), i.b("\n" + t), i.b("    </ol>"), i.b("\n" + t), i.b("</div>"), i.fl()
            }, partials: {}, subs: {}
        }), n.defaultTemplates["generic-block-header"] = new a.Template({
            code: function (e, n, t) {
                var i = this;
                return i.b(t = t || ""), i.b("<tr>"), i.b("\n" + t), i.b('    <td class="'), i.b(i.v(i.f("lineClass", e, n, 0))), i.b(" "), i.b(i.v(i.d("CSSLineClass.INFO", e, n, 0))), i.b('"></td>'), i.b("\n" + t), i.b('    <td class="'), i.b(i.v(i.d("CSSLineClass.INFO", e, n, 0))), i.b('">'), i.b("\n" + t), i.b('        <div class="'), i.b(i.v(i.f("contentClass", e, n, 0))), i.b(" "), i.b(i.v(i.d("CSSLineClass.INFO", e, n, 0))), i.b('">'), i.b(i.t(i.f("blockHeader", e, n, 0))), i.b("</div>"), i.b("\n" + t), i.b("    </td>"), i.b("\n" + t), i.b("</tr>"), i.fl()
            }, partials: {}, subs: {}
        }), n.defaultTemplates["generic-empty-diff"] = new a.Template({
            code: function (e, n, t) {
                var i = this;
                return i.b(t = t || ""), i.b("<tr>"), i.b("\n" + t), i.b('    <td class="'), i.b(i.v(i.d("CSSLineClass.INFO", e, n, 0))), i.b('">'), i.b("\n" + t), i.b('        <div class="'), i.b(i.v(i.f("contentClass", e, n, 0))), i.b(" "), i.b(i.v(i.d("CSSLineClass.INFO", e, n, 0))), i.b('">'), i.b("\n" + t), i.b("            File without changes"), i.b("\n" + t), i.b("        </div>"), i.b("\n" + t), i.b("    </td>"), i.b("\n" + t), i.b("</tr>"), i.fl()
            }, partials: {}, subs: {}
        }), n.defaultTemplates["generic-file-path"] = new a.Template({
            code: function (e, n, t) {
                var i = this;
                return i.b(t = t || ""), i.b('<span class="d2h-file-name-wrapper">'), i.b("\n" + t), i.b(i.rp("<fileIcon0", e, n, "    ")), i.b('    <span class="d2h-file-name">'), i.b(i.v(i.f("fileDiffName", e, n, 0))), i.b("</span>"), i.b("\n" + t), i.b(i.rp("<fileTag1", e, n, "    ")), i.b("</span>"), i.fl()
            }, partials: {"<fileIcon0": {name: "fileIcon", partials: {}, subs: {}}, "<fileTag1": {name: "fileTag", partials: {}, subs: {}}}, subs: {}
        }), n.defaultTemplates["generic-line"] = new a.Template({
            code: function (e, n, t) {
                var i = this;
                return i.b(t = t || ""), i.b("<tr>"), i.b("\n" + t), i.b('    <td class="'), i.b(i.v(i.f("lineClass", e, n, 0))), i.b(" "), i.b(i.v(i.f("type", e, n, 0))), i.b('">'), i.b("\n" + t), i.b("      "), i.b(i.t(i.f("lineNumber", e, n, 0))), i.b("\n" + t), i.b("    </td>"), i.b("\n" + t), i.b('    <td class="'), i.b(i.v(i.f("type", e, n, 0))), i.b('">'), i.b("\n" + t), i.b('        <div class="'), i.b(i.v(i.f("contentClass", e, n, 0))), i.b(" "), i.b(i.v(i.f("type", e, n, 0))), i.b('">'), i.b("\n" + t), i.s(i.f("prefix", e, n, 1), e, n, 0, 171, 247, "{{ }}") && (i.rs(e, n, (function (e, n, i) {
                    i.b('            <span class="d2h-code-line-prefix">'), i.b(i.t(i.f("prefix", e, n, 0))), i.b("</span>"), i.b("\n" + t)
                })), e.pop()), i.s(i.f("prefix", e, n, 1), e, n, 1, 0, 0, "") || (i.b('            <span class="d2h-code-line-prefix">&nbsp;</span>'), i.b("\n" + t)), i.s(i.f("content", e, n, 1), e, n, 0, 380, 454, "{{ }}") && (i.rs(e, n, (function (e, n, i) {
                    i.b('            <span class="d2h-code-line-ctn">'), i.b(i.t(i.f("content", e, n, 0))), i.b("</span>"), i.b("\n" + t)
                })), e.pop()), i.s(i.f("content", e, n, 1), e, n, 1, 0, 0, "") || (i.b('            <span class="d2h-code-line-ctn"><br></span>'), i.b("\n" + t)), i.b("        </div>"), i.b("\n" + t), i.b("    </td>"), i.b("\n" + t), i.b("</tr>"), i.fl()
            }, partials: {}, subs: {}
        }), n.defaultTemplates["generic-wrapper"] = new a.Template({
            code: function (e, n, t) {
                var i = this;
                return i.b(t = t || ""), i.b('<div class="d2h-wrapper">'), i.b("\n" + t), i.b("    "), i.b(i.t(i.f("content", e, n, 0))), i.b("\n" + t), i.b("</div>"), i.fl()
            }, partials: {}, subs: {}
        }), n.defaultTemplates["icon-file-added"] = new a.Template({
            code: function (e, n, t) {
                var i = this;
                return i.b(t = t || ""), i.b('<svg aria-hidden="true" class="d2h-icon d2h-added" height="16" title="added" version="1.1" viewBox="0 0 14 16"'), i.b("\n" + t), i.b('     width="14">'), i.b("\n" + t), i.b('    <path d="M13 1H1C0.45 1 0 1.45 0 2v12c0 0.55 0.45 1 1 1h12c0.55 0 1-0.45 1-1V2c0-0.55-0.45-1-1-1z m0 13H1V2h12v12zM6 9H3V7h3V4h2v3h3v2H8v3H6V9z"></path>'), i.b("\n" + t), i.b("</svg>"), i.fl()
            }, partials: {}, subs: {}
        }), n.defaultTemplates["icon-file-changed"] = new a.Template({
            code: function (e, n, t) {
                var i = this;
                return i.b(t = t || ""), i.b('<svg aria-hidden="true" class="d2h-icon d2h-changed" height="16" title="modified" version="1.1"'), i.b("\n" + t), i.b('     viewBox="0 0 14 16" width="14">'), i.b("\n" + t), i.b('    <path d="M13 1H1C0.45 1 0 1.45 0 2v12c0 0.55 0.45 1 1 1h12c0.55 0 1-0.45 1-1V2c0-0.55-0.45-1-1-1z m0 13H1V2h12v12zM4 8c0-1.66 1.34-3 3-3s3 1.34 3 3-1.34 3-3 3-3-1.34-3-3z"></path>'), i.b("\n" + t), i.b("</svg>"), i.fl()
            }, partials: {}, subs: {}
        }), n.defaultTemplates["icon-file-deleted"] = new a.Template({
            code: function (e, n, t) {
                var i = this;
                return i.b(t = t || ""), i.b('<svg aria-hidden="true" class="d2h-icon d2h-deleted" height="16" title="removed" version="1.1"'), i.b("\n" + t), i.b('     viewBox="0 0 14 16" width="14">'), i.b("\n" + t), i.b('    <path d="M13 1H1C0.45 1 0 1.45 0 2v12c0 0.55 0.45 1 1 1h12c0.55 0 1-0.45 1-1V2c0-0.55-0.45-1-1-1z m0 13H1V2h12v12zM11 9H3V7h8v2z"></path>'), i.b("\n" + t), i.b("</svg>"), i.fl()
            }, partials: {}, subs: {}
        }), n.defaultTemplates["icon-file-renamed"] = new a.Template({
            code: function (e, n, t) {
                var i = this;
                return i.b(t = t || ""), i.b('<svg aria-hidden="true" class="d2h-icon d2h-moved" height="16" title="renamed" version="1.1"'), i.b("\n" + t), i.b('     viewBox="0 0 14 16" width="14">'), i.b("\n" + t), i.b('    <path d="M6 9H3V7h3V4l5 4-5 4V9z m8-7v12c0 0.55-0.45 1-1 1H1c-0.55 0-1-0.45-1-1V2c0-0.55 0.45-1 1-1h12c0.55 0 1 0.45 1 1z m-1 0H1v12h12V2z"></path>'), i.b("\n" + t), i.b("</svg>"), i.fl()
            }, partials: {}, subs: {}
        }), n.defaultTemplates["icon-file"] = new a.Template({
            code: function (e, n, t) {
                var i = this;
                return i.b(t = t || ""), i.b('<svg aria-hidden="true" class="d2h-icon" height="16" version="1.1" viewBox="0 0 12 16" width="12">'), i.b("\n" + t), i.b('    <path d="M6 5H2v-1h4v1zM2 8h7v-1H2v1z m0 2h7v-1H2v1z m0 2h7v-1H2v1z m10-7.5v9.5c0 0.55-0.45 1-1 1H1c-0.55 0-1-0.45-1-1V2c0-0.55 0.45-1 1-1h7.5l3.5 3.5z m-1 0.5L8 2H1v12h10V5z"></path>'), i.b("\n" + t), i.b("</svg>"), i.fl()
            }, partials: {}, subs: {}
        }), n.defaultTemplates["line-by-line-file-diff"] = new a.Template({
            code: function (e, n, t) {
                var i = this;
                return i.b(t = t || ""), i.b('<div id="'), i.b(i.v(i.f("fileHtmlId", e, n, 0))), i.b('" class="d2h-file-wrapper" data-lang="'), i.b(i.v(i.d("file.language", e, n, 0))), i.b('">'), i.b("\n" + t), i.b('    <div class="d2h-file-header">'), i.b("\n" + t), i.b("    "), i.b(i.t(i.f("filePath", e, n, 0))), i.b("\n" + t), i.b("    </div>"), i.b("\n" + t), i.b('    <div class="d2h-file-diff">'), i.b("\n" + t), i.b('        <div class="d2h-code-wrapper">'), i.b("\n" + t), i.b('            <table class="d2h-diff-table">'), i.b("\n" + t), i.b('                <tbody class="d2h-diff-tbody">'), i.b("\n" + t), i.b("                "), i.b(i.t(i.f("diffs", e, n, 0))), i.b("\n" + t), i.b("                </tbody>"), i.b("\n" + t), i.b("            </table>"), i.b("\n" + t), i.b("        </div>"), i.b("\n" + t), i.b("    </div>"), i.b("\n" + t), i.b("</div>"), i.fl()
            }, partials: {}, subs: {}
        }), n.defaultTemplates["line-by-line-numbers"] = new a.Template({
            code: function (e, n, t) {
                var i = this;
                return i.b(t = t || ""), i.b('<div class="line-num1">'), i.b(i.v(i.f("oldNumber", e, n, 0))), i.b("</div>"), i.b("\n" + t), i.b('<div class="line-num2">'), i.b(i.v(i.f("newNumber", e, n, 0))), i.b("</div>"), i.fl()
            }, partials: {}, subs: {}
        }), n.defaultTemplates["side-by-side-file-diff"] = new a.Template({
            code: function (e, n, t) {
                var i = this;
                return i.b(t = t || ""), i.b('<div id="'), i.b(i.v(i.f("fileHtmlId", e, n, 0))), i.b('" class="d2h-file-wrapper" data-lang="'), i.b(i.v(i.d("file.language", e, n, 0))), i.b('">'), i.b("\n" + t), i.b('    <div class="d2h-file-header">'), i.b("\n" + t), i.b("      "), i.b(i.t(i.f("filePath", e, n, 0))), i.b("\n" + t), i.b("    </div>"), i.b("\n" + t), i.b('    <div class="d2h-files-diff">'), i.b("\n" + t), i.b('        <div class="d2h-file-side-diff">'), i.b("\n" + t), i.b('            <div class="d2h-code-wrapper">'), i.b("\n" + t), i.b('                <table class="d2h-diff-table">'), i.b("\n" + t), i.b('                    <tbody class="d2h-diff-tbody">'), i.b("\n" + t), i.b("                    "), i.b(i.t(i.d("diffs.left", e, n, 0))), i.b("\n" + t), i.b("                    </tbody>"), i.b("\n" + t), i.b("                </table>"), i.b("\n" + t), i.b("            </div>"), i.b("\n" + t), i.b("        </div>"), i.b("\n" + t), i.b('        <div class="d2h-file-side-diff">'), i.b("\n" + t), i.b('            <div class="d2h-code-wrapper">'), i.b("\n" + t), i.b('                <table class="d2h-diff-table">'), i.b("\n" + t), i.b('                    <tbody class="d2h-diff-tbody">'), i.b("\n" + t), i.b("                    "), i.b(i.t(i.d("diffs.right", e, n, 0))), i.b("\n" + t), i.b("                    </tbody>"), i.b("\n" + t), i.b("                </table>"), i.b("\n" + t), i.b("            </div>"), i.b("\n" + t), i.b("        </div>"), i.b("\n" + t), i.b("    </div>"), i.b("\n" + t), i.b("</div>"), i.fl()
            }, partials: {}, subs: {}
        }), n.defaultTemplates["tag-file-added"] = new a.Template({
            code: function (e, n, t) {
                var i = this;
                return i.b(t = t || ""), i.b('<span class="d2h-tag d2h-added d2h-added-tag">ADDED</span>'), i.fl()
            }, partials: {}, subs: {}
        }), n.defaultTemplates["tag-file-changed"] = new a.Template({
            code: function (e, n, t) {
                var i = this;
                return i.b(t = t || ""), i.b('<span class="d2h-tag d2h-changed d2h-changed-tag">CHANGED</span>'), i.fl()
            }, partials: {}, subs: {}
        }), n.defaultTemplates["tag-file-deleted"] = new a.Template({
            code: function (e, n, t) {
                var i = this;
                return i.b(t = t || ""), i.b('<span class="d2h-tag d2h-deleted d2h-deleted-tag">DELETED</span>'), i.fl()
            }, partials: {}, subs: {}
        }), n.defaultTemplates["tag-file-renamed"] = new a.Template({
            code: function (e, n, t) {
                var i = this;
                return i.b(t = t || ""), i.b('<span class="d2h-tag d2h-moved d2h-moved-tag">RENAMED</span>'), i.fl()
            }, partials: {}, subs: {}
        })
    }])
}));
