<style lang="less">
@import "./login.less";
</style>

<template>
  <div class="login">
    <div class="login-con">
      <Card icon="log-in" title="SVNAdmin V2.5.3" :bordered="false">
        <div class="form-con">
          <Form
            ref="formUserLogin"
            :model="formUserLogin"
            :rules="ruleValidateLogin"
            @keydown.enter.native="Submit('formUserLogin')"
          >
            <FormItem prop="user_name">
              <Input
                v-model="formUserLogin.user_name"
                placeholder="请输入用户名"
              >
                <span slot="prepend">
                  <Icon :size="16" type="ios-person"></Icon>
                </span>
              </Input>
            </FormItem>
            <FormItem prop="user_pass">
              <Input
                type="password"
                password
                v-model="formUserLogin.user_pass"
                placeholder="请输入密码"
              >
                <span slot="prepend">
                  <Icon :size="14" type="md-lock"></Icon>
                </span>
              </Input>
            </FormItem>
            <FormItem>
              <Select
                v-model="formUserLogin.user_role"
                :transfer="true"
                @on-change="ChangeSelect"
              >
                <Option value="1">管理人员</Option>
                <Option value="3">子管理员</Option>
                <Option value="2">SVN用户</Option>
              </Select>
            </FormItem>
            <FormItem prop="code" v-if="verifyOption">
              <Row>
                <Col span="11"
                  ><Input
                    v-model="formUserLogin.code"
                    placeholder="请输入验证码"
                  ></Input
                ></Col>
                <Col span="1"></Col>
                <Col span="12">
                  <img
                    @click="GetVerifyCode"
                    :src="formUserLogin.base64"
                    :alt="loadingGetVerifyCode"
                    style="width: 100%; cursor: pointer"
                  />
                </Col>
              </Row>
            </FormItem>
            <FormItem>
              <Button
                type="primary"
                long
                @click="Submit('formUserLogin')"
                :loading="loadingLogin"
                >登录</Button
              >
            </FormItem>
          </Form>
        </div>
      </Card>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      /**
       * 加载
       */
      loadingGetVerifyCode: "loading......",
      loadingLogin: false,

      /**
       * 组件状态
       */
      verifyOption: false,

      /**
       * 表单
       */
      // 登录表单
      formUserLogin: {
        user_name: "",
        user_pass: "",
        user_role: "",
        code: "",
        uuid: "",
        base64: "",
      },

      /**
       * 校验规则
       */
      // 登录校验规则
      ruleValidateLogin: {
        user_name: [
          { required: true, message: "用户名不能为空", trigger: "blur" },
        ],
        user_pass: [
          { required: true, message: "密码不能为空", trigger: "blur" },
        ],
        code: [{ required: true, message: "验证码不能为空", trigger: "blur" }],
      },
    };
  },
  computed: {},
  created() {},
  mounted() {
    var that = this;
    //还原下拉
    that.formUserLogin.user_role = localStorage.user_role
      ? localStorage.user_role
      : "2";
    if (sessionStorage.token) {
      that.$Message.success("已有登录信息 自动跳转中...");
      setTimeout(function () {
        that.$router.push({ name: sessionStorage.firstRoute });
      }, 2000);
    } else {
      that.GetVerifyOption();
    }
  },
  methods: {
    //记录下拉
    ChangeSelect(value) {
      localStorage.setItem("user_role", value);
    },
    //表单提交
    Submit(formName) {
      this.$refs[formName].validate((valid) => {
        if (valid) {
          this.Login();
        } else {
          return false;
        }
      });
    },
    /**
     * 获取验证码选项
     */
    GetVerifyOption() {
      var that = this;
      var data = {};
      that.$axios
        .post("api.php?c=Setting&a=GetVerifyOption&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            if (result.data.enable == true) {
              that.verifyOption = true;
              that.GetVerifyCode();
            } else {
              that.verifyOption = false;
            }
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    /**
     * 请求验证码
     */
    GetVerifyCode() {
      var that = this;
      that.formUserLogin.base64 = "";
      that.loadingGetVerifyCode = "loading......";
      var data = {};
      that.$axios
        .post("api.php?c=Common&a=GetVerifyCode&t=web", data)
        .then(function (response) {
          var result = response.data;
          if (result.status == 1) {
            that.formUserLogin.uuid = result.data.uuid;
            that.formUserLogin.base64 = result.data.base64;
          } else {
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
    //登录
    Login() {
      var that = this;
      that.loadingLogin = true;
      var data = {
        user_name: that.formUserLogin.user_name,
        user_pass: that.formUserLogin.user_pass,
        user_role: that.formUserLogin.user_role,
        uuid: that.formUserLogin.uuid,
        code: that.formUserLogin.code,
      };
      that.$axios
        .post("api.php?c=Common&a=Login&t=web", data)
        .then(function (response) {
          that.loadingLogin = false;
          var result = response.data;
          if (result.status == 1) {
            //存储
            sessionStorage.setItem("token", result.data.token);
            sessionStorage.setItem("user_name", result.data.user_name);
            sessionStorage.setItem("user_role_id", result.data.user_role_id);
            sessionStorage.setItem(
              "user_role_name",
              result.data.user_role_name
            );
            sessionStorage.setItem("route", JSON.stringify(result.data.route));
            sessionStorage.setItem(
              "functions",
              JSON.stringify(result.data.functions)
            );

            that.$Message.success(result.message);

            if (result.data.user_role_id == 1) {
              //管理员跳转到首页
              sessionStorage.setItem("firstRoute", "index");
            } else if (result.data.user_role_id == 2) {
              //用户跳转到仓库页
              sessionStorage.setItem("firstRoute", "repositoryInfo");
            } else if (result.data.user_role_id == 3) {
              //子管理员跳转到有权限的首个页面
              sessionStorage.setItem(
                "firstRoute",
                result.data.route.children[0].name
              );
            }
            that.$router.push({ name: sessionStorage.firstRoute });
          } else {
            that.GetVerifyOption();
            that.$Message.error({ content: result.message, duration: 2 });
          }
        })
        .catch(function (error) {
          that.loadingLogin = false;
          console.log(error);
          that.$Message.error("出错了 请联系管理员！");
        });
    },
  },
};
</script>